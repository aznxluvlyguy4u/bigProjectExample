<?php

namespace AppBundle\Cache;

use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalCache;
use AppBundle\Entity\AnimalCacheRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\ProductionUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use \Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnimalCacher
{
    const GENERAL_NULL_FILLER = '-';
    const EMPTY_DATE_OF_BIRTH = '-';
    const NEUTER_STRING = '-';
    const FLUSH_BATCH_SIZE = 1000;
    const UPDATE_BATCH_SIZE = 10000;

    //Cache setting
    const CHECK_ANIMAL_CACHE_BEFORE_PERSISTING = true;


    /**
     * @param Connection $conn
     * @param CommandUtil|null $cmdUtil
     */
    public static function cacheAllAnimalsBySqlBatchQueries(Connection $conn, CommandUtil $cmdUtil = null)
    {
        DoctrineUtil::updateTableSequence($conn, ['animal_cache']);
        $sql = "INSERT INTO animal_cache (animal_id)
                  SELECT a.id FROM animal a
                  LEFT JOIN animal_cache c ON c.animal_id = a.id
                  WHERE c.id ISNULL";
        $blankInsertCount = SqlUtil::updateWithCount($conn, $sql);
        DoctrineUtil::updateTableSequence($conn, ['animal_cache']);
        if ($cmdUtil !== null) { $cmdUtil->writeln($blankInsertCount . ' new blank animal_cache records inserted'); }

        $updateCount = ProductionCacher::updateAllProductionValues($conn);
        $cmdUtil->writeln($updateCount.' production values updated');

        $updateCount = NLingCacher::updateAllNLingValues($conn);
        $cmdUtil->writeln($updateCount.' n-ling values updated');

        $updateCount = ExteriorCacher::updateAllExteriors($conn);
        $cmdUtil->writeln($updateCount.' exterior animalCache records updated' );

        $updateCount = WeightCacher::updateAllWeights($conn);
        $cmdUtil->writeln($updateCount.' weight animalCache records updated' );

        $updateCount = TailLengthCacher::updateAll($conn);
        $cmdUtil->writeln($updateCount.' tailLength animalCache records updated' );
    }


    /**
     * @param ObjectManager $em
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param string $ignoreCacheBeforeDateString
     * @param CommandUtil $cmdUtil
     */
    public static function cacheAllAnimals(ObjectManager $em, $cmdUtil = null, $ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null)
    {
        self::cacheAnimals($em, $ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $cmdUtil);
    }


    /**
     * @param ObjectManager $em
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param null $ignoreCacheBeforeDateString
     * @param null $cmdUtil
     * @param $locationId
     */
    public static function cacheAnimalsOfLocationId(ObjectManager $em, $locationId, $cmdUtil = null, $ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null)
    {
        self::cacheAnimals($em, $ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $cmdUtil, $locationId);
    }
    

    /**
     * @param ObjectManager $em
     * @param bool $ignoreAnimalsWithAnExistingCache
     * @param string $ignoreCacheBeforeDateString
     * @param CommandUtil $cmdUtil
     * @param int $locationId
     */
    private static function cacheAnimals(ObjectManager $em, $ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $cmdUtil = null, $locationId = null)
    {
        $flushPerRecord = false;

        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $em->getRepository(AnimalCache::class);
        $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataPerLocation($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $locationId);
        if(count($animalCacherInputData) == 0) { return; }

        $count = 0;

        /** @var Connection $conn */
        $conn = $em->getConnection();

        $cachedAnimalIds = self::getAnimalIdsOfAlreadyCachedAnimals($conn);

        if($cmdUtil instanceof CommandUtil) {
            $cmdUtil->setStartTimeAndPrintIt(count($animalCacherInputData) + 1, 1, 'Generating animal cache records');
            foreach ($animalCacherInputData as $record) {
                $animalId = $record['animal_id'];
                if(!array_key_exists($animalId, $cachedAnimalIds) || !$ignoreAnimalsWithAnExistingCache) { //Double checks for duplicates
                    self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $record['animal_cache_id'] != null, $flushPerRecord);
                }
                if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
                $cmdUtil->advanceProgressBar(1);
            }
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        } else {
            foreach ($animalCacherInputData as $record) {
                $animalId = $record['animal_id'];
                if(!array_key_exists($animalId, $cachedAnimalIds) || !$ignoreAnimalsWithAnExistingCache) { //Double checks for duplicates
                    self::cacheById($em, $record['animal_id'], $record['gender'], $record['date_of_birth'], $record['breed_type'], $record['animal_cache_id'] != null, $flushPerRecord);
                }
                if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
            }
        }

        if(!$flushPerRecord) { $em->flush(); }

        //DuplicateCheck
        self::deleteDuplicateAnimalCacheRecords($em);

        DoctrineUtil::updateTableSequence($conn, ['animal_cache']);
    }


    /**
     * @param ObjectManager $em
     * @param boolean $ignoreAnimalsWithAnExistingCache
     * @param string $ignoreCacheBeforeDateString
     * @param CommandUtil $cmdUtil
     * @param int $locationId
     */
    public static function cacheAnimalsAndAscendantsByLocationId(ObjectManager $em, $ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $cmdUtil = null, $locationId = null) {
        $filter = $locationId == null ? '' : " WHERE location_id = ".$locationId;
        $sql = "SELECT id FROM animal ".$filter;
        $results = $em->getConnection()->query($sql)->fetchAll();
        $animalIds = [];
        foreach ($results as $result) {
            $animalIds[] = $result['id'];
        }

        self::cacheAnimalsAndAscendantsByIds($em, $ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $cmdUtil, $animalIds);
    }


    /**
     * @param ObjectManager $em
     * @param boolean $ignoreAnimalsWithAnExistingCache
     * @param string $ignoreCacheBeforeDateString
     * @param CommandUtil $cmdUtil
     * @param array $animalIds
     */
    public static function cacheAnimalsAndAscendantsByIds(ObjectManager $em, $ignoreAnimalsWithAnExistingCache = true, $ignoreCacheBeforeDateString = null, $cmdUtil = null, $animalIds = []) {

        $flushPerRecord = false;

        $cachedAnimalIds = self::getAnimalIdsOfAlreadyCachedAnimals($em->getConnection());

        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $em->getRepository(AnimalCache::class);

        $count = 0;

        if($cmdUtil instanceof CommandUtil) {
            $cmdUtil->setStartTimeAndPrintIt(count($animalIds) + 1, 1, 'Generating animal cache records');

            foreach ($animalIds as $animalIdChild) {
                if(is_int($animalIdChild)) {

                    $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataForAnimalAndAscendants($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $animalIdChild);

                    foreach ($animalCacherInputData as $record) {
                        $animalId = $record['animal_id'];
                        if(!array_key_exists($animalId, $cachedAnimalIds) || !$ignoreAnimalsWithAnExistingCache) { //THIS PREVENTS DUPLICATES!
                            self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $record['animal_cache_id'] != null, $flushPerRecord);

                            //Add animalId to array to check for duplicates
                            $cachedAnimalIds[$animalId] = $animalId;
                        }
                    }
                    if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
                    $cmdUtil->advanceProgressBar(1);
                }
            }
            $cmdUtil->setEndTimeAndPrintFinalOverview();

        } else {
            foreach ($animalIds as $animalIdChild) {
                if (is_int($animalIdChild)) {

                    $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataForAnimalAndAscendants($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $animalIdChild);

                    foreach ($animalCacherInputData as $record) {
                        $animalId = $record['animal_id'];
                        if(!array_key_exists($animalId, $cachedAnimalIds) || !$ignoreAnimalsWithAnExistingCache) { //THIS PREVENTS DUPLICATES!
                            self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $record['animal_cache_id'] != null, $flushPerRecord);

                            //Add animalId to array to check for duplicates
                            $cachedAnimalIds[$animalId] = $animalId;
                        }
                    }
                    if ($count++ % self::FLUSH_BATCH_SIZE == 0) {
                        $em->flush();
                    }
                }
            }
        }

        if(!$flushPerRecord) { $em->flush(); }

        //DuplicateCheck
        self::deleteDuplicateAnimalCacheRecords($em);
    }


    /**
     * @param Connection $conn
     * @param boolean $sortAnimalIds
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getAnimalIdsOfAlreadyCachedAnimals(Connection $conn, $sortAnimalIds = false)
    {
        $sql = "SELECT animal_id FROM animal_cache";
        $results = $conn->query($sql)->fetchAll();
        return SqlUtil::getSingleValueGroupedSqlResults('animal_id', $results, true, $sortAnimalIds);
    }


    /**
     * @param CommandUtil|OutputInterface $commandUtilOrOutputInterface
     * @param int $count
     */
    public static function printEmptyAnimalCacheRecords($commandUtilOrOutputInterface, $count)
    {
        $message = $count == 0 ? 'There are no missing animal_cache records!' : 'There are '.$count.' missing animal_cache records';
        $commandUtilOrOutputInterface->writeln($message);
    }


    /**
     * @param Connection $conn
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getMissingAnimalCacheCount(Connection $conn)
    {
        $sql = "SELECT COUNT(*) FROM animal a LEFT JOIN animal_cache c ON c.animal_id = a.id WHERE c.id ISNULL";
        return intval($conn->query($sql)->fetch()['count']);
    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function cacheAllAnimalsByLocationGroupsIncludingAscendants(ObjectManager $em, CommandUtil $cmdUtil = null)
    {
        $sortAlreadyCachedAnimalIds = true;
        
        $defaultMinLocationId = 0;
        do {
            $minLocationId = $cmdUtil->generateQuestion('Select min locationId (must be an integer, default = '.$defaultMinLocationId.')', $defaultMinLocationId);
        } while (!ctype_digit($minLocationId) && !is_int($minLocationId));
        $cmdUtil->writeln('Choice min locationId: '.$minLocationId);

        /** @var Connection $conn */
        $conn = $em->getConnection();
        
        $sql = "SELECT id FROM location WHERE is_active = TRUE AND id >= ".$minLocationId;
        $results = $conn->query($sql)->fetchAll();
        $locationIds = SqlUtil::getSingleValueGroupedSqlResults('id', $results, true, true);

        $totalLocationCount = count($locationIds);
        if($totalLocationCount == 0) {
            $cmdUtil->writeln('No locations where found with ids greater or equal to '.$minLocationId);
            return;
        }

        $missingAnimalCacheCount = self::getMissingAnimalCacheCount($conn);
        self::printEmptyAnimalCacheRecords($cmdUtil, $missingAnimalCacheCount);
        if($missingAnimalCacheCount == 0) { return; }

        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $em->getRepository(AnimalCache::class);

        $processedAnimalsCount = 0;
        $locationCount = 0;

        $cmdUtil->setStartTimeAndPrintIt($missingAnimalCacheCount, 1, 'Generating animal cache records ordered by locations ...');
        foreach ($locationIds as $locationId) {

            $locationCount++;
            $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataForAnimalAndAscendantsByLocationId(true, null, $locationId);

            $totalCount = count($animalCacherInputData);
            if($totalCount == 0) {
                continue;
            }

            //Recheck already cached animals at the start of each loop to prevent overlap!
            $cachedAnimalIds = self::getAnimalIdsOfAlreadyCachedAnimals($conn, $sortAlreadyCachedAnimalIds);
            $maxAnimalCacheId = SqlUtil::getMaxId($conn, 'animal_cache');

            $insertString = '';

            $loopCount = 0;
            $animalBatchCount = 0;

            foreach ($animalCacherInputData as $record) {
                $loopCount++;
                $animalId = $record['animal_id'];
                if (!array_key_exists($animalId, $cachedAnimalIds)) { //Double checks for duplicates
                    $insertString = $insertString . self::getCacheByIdInsertString($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $maxAnimalCacheId) . ',';
                    $maxAnimalCacheId++;
                    $animalBatchCount++;
                }

                if ($loopCount == $totalCount || ($animalBatchCount % self::FLUSH_BATCH_SIZE == 0 && $loopCount != 0)) {
                    self::insertByBatch($conn, $insertString);
                    $insertString = '';
                    $processedAnimalsCount += $animalBatchCount;
                    $animalBatchCount = 0;
                }

                $cmdUtil->advanceProgressBar(1, 'Locations processed: '.$locationCount.'/'.$totalLocationCount.'(currentId: '.$locationId.')  New inserted animalCache inBatch|processed: ' . $animalBatchCount . '|' . $processedAnimalsCount);
            }

            //DuplicateCheck after each loop
            self::deleteDuplicateAnimalCacheRecords($em);

            //Force garbage collection
            $animalCacherInputData = null;
            gc_collect_cycles();
        }
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }



    /**
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param int $locationId
     * @return int
     */
    public static function cacheAnimalsBySqlInsert(ObjectManager $em, CommandUtil $cmdUtil = null, $locationId = null)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $em->getRepository(AnimalCache::class);
        $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataPerLocation(true, null, $locationId);
        $totalCount = count($animalCacherInputData);
        if($totalCount == 0) { return 0; }

        $cachedAnimalIds = self::getAnimalIdsOfAlreadyCachedAnimals($conn);
        

        $insertString = '';
        $sql = "SELECT MAX(id) FROM animal_cache";
        $maxAnimalCacheId = $conn->query($sql)->fetch()['max'];

        $loopCount = 0;
        $batchCount = 0;
        $processedCount = 0;

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($totalCount + 1, 1, 'Generating animal cache records'); }

        foreach ($animalCacherInputData as $record) {
            $loopCount++;
            $animalId = $record['animal_id'];
            if(!array_key_exists($animalId, $cachedAnimalIds)) { //Double checks for duplicates
                $insertString = $insertString . self::getCacheByIdInsertString($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $maxAnimalCacheId).',';
                $maxAnimalCacheId++;
                $batchCount++;
            }


            if($loopCount == $totalCount || ($batchCount%self::FLUSH_BATCH_SIZE == 0 && $loopCount != 0)) {
                self::insertByBatch($conn, $insertString);
                $insertString = '';
                $processedCount += $batchCount;
                $batchCount = 0;
            }


            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'New inserted animalCache inBatch|processed: '.$batchCount.'|'.$processedCount); }
        }

        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }


        //DuplicateCheck
        self::deleteDuplicateAnimalCacheRecords($em);

        DoctrineUtil::updateTableSequence($conn, ['animal_cache']);

        return $processedCount;
    }


    private static function insertByBatch(Connection $conn, $insertString)
    {
        $insertString = rtrim($insertString, ',');
        $sql = "INSERT INTO animal_cache (id, log_date, animal_id, dutch_breed_status,
						  n_ling, production_age, litter_count, total_offspring_count, born_alive_offspring_count, gave_birth_as_one_year_old, last_weight, weight_measurement_date, kind, skull, muscularity, proportion, progress,
						  exterior_type, leg_work, fur, general_appearance, height, breast_depth,
						  torso_length, markings, exterior_measurement_date						  
						)VALUES ".$insertString;
        $conn->exec($sql);
    }

    
    private static function getCacheByIdInsertString(ObjectManager $em, $animalId, $gender, $dateOfBirthString, $breedType, $maxAnimalCacheId)
    {
        //Animal Entity Data
        $gender = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getGenderInDutch($gender, self::NEUTER_STRING),true);
        $dutchBreedStatus = SqlUtil::getNullCheckedValueForSqlQuery(Translation::getFirstLetterTranslatedBreedType($breedType),true);

        //Litter Data
        $productionString = SqlUtil::getNullCheckedValueForSqlQuery(self::generateProductionString($em, $animalId, $gender, $dateOfBirthString),true);
        $nLing = SqlUtil::getNullCheckedValueForSqlQuery(self::getNLingData($em, $animalId),true);
        $productionAge = SqlUtil::getNullCheckedValueForSqlQuery(ProductionUtil::getProductionAgeFromProductionString($productionString),false);
        $litterCount = SqlUtil::getNullCheckedValueForSqlQuery(ProductionUtil::getLitterCountFromProductionString($productionString),false);
        $totalOffspring = SqlUtil::getNullCheckedValueForSqlQuery(ProductionUtil::getTotalOffspringCountFromProductionString($productionString),false);
        $totalBornOffspring = SqlUtil::getNullCheckedValueForSqlQuery(ProductionUtil::getBornAliveCountFromProductionString($productionString),false);
        $hasOneYearMark = StringUtil::getBooleanAsString(ProductionUtil::hasOneYearMark($productionString), 'FALSE');

        //Weight Data
        /** @var WeightRepository $weightRepository */
        $weightRepository = $em->getRepository(Weight::class);
        $lastWeightMeasurementData = $weightRepository->getLatestWeightBySql($animalId);
        $weight = SqlUtil::getNullCheckedValueForSqlQuery($lastWeightMeasurementData[JsonInputConstant::WEIGHT],true);
        $isBirthWeight = $lastWeightMeasurementData[JsonInputConstant::IS_BIRTH_WEIGHT];
        $weightMeasurementDateString = SqlUtil::getNullCheckedValueForSqlQuery($lastWeightMeasurementData[JsonInputConstant::MEASUREMENT_DATE],true);
        $weightExists = $weightMeasurementDateString != null;

        //Exterior Data
        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $em->getRepository(Exterior::class);
        $exteriorData = $exteriorRepository->getLatestExteriorBySql($animalId);
        $kind = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::KIND],true);
        $skull = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::SKULL],true);
        $muscularity = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::MUSCULARITY],true);
        $proportion = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::PROPORTION],true);
        $progress = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::PROGRESS],true);
        $exteriorType = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::EXTERIOR_TYPE],true);
        $legWork = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::LEG_WORK],true);
        $fur = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::FUR],true);
        $generalAppearance = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::GENERAL_APPEARANCE],true);
        $height = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::HEIGHT],true);
        $breastDepth = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::BREAST_DEPTH],true);
        $torsoLength = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::TORSO_LENGTH],true);
        $markings = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::MARKINGS],true);
        $exteriorMeasurementDateString = SqlUtil::getNullCheckedValueForSqlQuery($exteriorData[JsonInputConstant::MEASUREMENT_DATE],true);

        $logDate = TimeUtil::getLogDateString();
        $maxAnimalCacheId++;

        return "(".$maxAnimalCacheId.",'".$logDate."',".$animalId.",".$dutchBreedStatus
            .",".$nLing.",".$productionAge.",".$litterCount.",".$totalOffspring.",".$totalBornOffspring.",".$hasOneYearMark
            .",".$weight.",".$weightMeasurementDateString
            .",".$kind.",".$skull.",".$muscularity.",".$proportion.",".$progress.",".$exteriorType.",".$legWork.",".$fur
            .",".$generalAppearance.",".$height.",".$breastDepth.",".$torsoLength.",".$markings.",".$exteriorMeasurementDateString.")";
    }

    /**
     * @param EntityManagerInterface $em
     * @param Animal $animal
     * @param bool $flush
     */
    public static function cacheByAnimal(EntityManagerInterface $em, Animal $animal, $flush = true) {

        $animalId = $animal->getId();
        if($animalId != null) {
            self::cacheById($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString(), $animal->getBreedType(), false, $flush);
        }
    }

    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @param string $gender
     * @param string $dateOfBirthString
     * @param string $breedType
     * @param boolean $isUpdate
     * @param boolean $flush
     */
    public static function cacheById(ObjectManager $em, $animalId, $gender, $dateOfBirthString, $breedType, $isUpdate, $flush = true)
    {
        //Animal Entity Data
        $gender = Translation::getGenderInDutch($gender, self::NEUTER_STRING);
        $dutchBreedStatus = Translation::getFirstLetterTranslatedBreedType($breedType);

        //Litter Data
        $nLing = self::getNLingData($em, $animalId);
        $productionString = self::generateProductionString($em, $animalId, $gender, $dateOfBirthString);
        $productionAge = ProductionUtil::getProductionAgeFromProductionString($productionString);
        $litterCount = ProductionUtil::getLitterCountFromProductionString($productionString);
        $totalOffspring = ProductionUtil::getTotalOffspringCountFromProductionString($productionString);
        $totalBornOffspring = ProductionUtil::getBornAliveCountFromProductionString($productionString);
        $hasOneYearMark = ProductionUtil::hasOneYearMark($productionString);

        //Weight Data
        /** @var WeightRepository $weightRepository */
        $weightRepository = $em->getRepository(Weight::class);
        $lastWeightMeasurementData = $weightRepository->getLatestWeightBySql($animalId);
        $weight = $lastWeightMeasurementData[JsonInputConstant::WEIGHT];
        $isBirthWeight = $lastWeightMeasurementData[JsonInputConstant::IS_BIRTH_WEIGHT];
        $weightMeasurementDateString = $lastWeightMeasurementData[JsonInputConstant::MEASUREMENT_DATE];
        $weightExists = $weightMeasurementDateString != null;

        //Exterior Data
        /** @var ExteriorRepository $exteriorRepository */
        $exteriorRepository = $em->getRepository(Exterior::class);
        $exteriorData = $exteriorRepository->getLatestExteriorBySql($animalId);
        $kind = $exteriorData[JsonInputConstant::KIND];
        $skull = $exteriorData[JsonInputConstant::SKULL];
        $muscularity = $exteriorData[JsonInputConstant::MUSCULARITY];
        $proportion = $exteriorData[JsonInputConstant::PROPORTION];
        $progress = $exteriorData[JsonInputConstant::PROGRESS];
        $exteriorType = $exteriorData[JsonInputConstant::EXTERIOR_TYPE];
        $legWork = $exteriorData[JsonInputConstant::LEG_WORK];
        $fur = $exteriorData[JsonInputConstant::FUR];
        $generalAppearance = $exteriorData[JsonInputConstant::GENERAL_APPEARANCE];
        $height = $exteriorData[JsonInputConstant::HEIGHT];
        $breastDepth = $exteriorData[JsonInputConstant::BREAST_DEPTH];
        $torsoLength = $exteriorData[JsonInputConstant::TORSO_LENGTH];
        $markings = $exteriorData[JsonInputConstant::MARKINGS];
        $exteriorMeasurementDateString = $exteriorData[JsonInputConstant::MEASUREMENT_DATE];
        $exteriorExists = $exteriorMeasurementDateString != null;
        $exteriorMeasurementDate = new \DateTime($exteriorMeasurementDateString);
        
        //TODO Still blank at the moment
        $predicate = null;


        //Clean database
        if($exteriorMeasurementDateString == '') {
            $exteriorMeasurementDateString = null;
        }


        $logDate = TimeUtil::getLogDateString();

        /** @var AnimalCacheRepository $repository */
        $repository = $em->getRepository(AnimalCache::class);

        if($isUpdate) {
            $record = $repository->findOneBy(['animalId' => $animalId]);
            if($record == null) { $record = new AnimalCache(); }
            $record->setLogDate(new \DateTime()); //update logDate
        } else {
            if(self::CHECK_ANIMAL_CACHE_BEFORE_PERSISTING) {
                $record = $repository->findOneBy(['animalId' => $animalId]);
                if($record == null) { $record = new AnimalCache(); }
                $record->setLogDate(new \DateTime()); //update logDate
            } else {
                //New record
                $record = new AnimalCache();
            }
        }

        $record->setProductionAge($productionAge);
        $record->setLitterCount($litterCount);
        $record->setTotalOffspringCount($totalOffspring);
        $record->setBornAliveOffspringCount($totalBornOffspring);
        $record->setGaveBirthAsOneYearOld($hasOneYearMark);

        $record->setAnimalId($animalId);
        $record->setDutchBreedStatus($dutchBreedStatus);
        $record->setPredicate($predicate);
        $record->setNLing($nLing);
        if($weightExists) {
            $record->setLastWeight($weight);
            $record->setWeightMeasurementDateByDateString($weightMeasurementDateString);
        }
        if($exteriorExists) {
            if($record->getKind() != $kind || $record->getSkull() != $skull || $record->getMuscularity() != $muscularity
                || $record->getProportion() != $proportion || $record->getProgress() !=  $progress
                || $record->getExteriorType() != $exteriorType || $record->getLegWork() != $legWork
                || $record->getFur() != $fur || $record->getGeneralAppearance() != $generalAppearance
                || $record->getHeight() != $height || $record->getBreastDepth() != $breastDepth
                || $record->getTorsoLength() != $torsoLength || $record->getMarkings() != $markings
                || $record->getExteriorMeasurementDate() != $exteriorMeasurementDate) {

                $record->setKind($kind);
                $record->setSkull($skull);
                $record->setMuscularity($muscularity);
                $record->setProportion($proportion);
                $record->setProgress($progress);
                $record->setExteriorType($exteriorType);
                $record->setLegWork($legWork);
                $record->setFur($fur);
                $record->setGeneralAppearance($generalAppearance);
                $record->setHeight($height);
                $record->setBreastDepth($breastDepth);
                $record->setTorsoLength($torsoLength);
                $record->setMarkings($markings);
                $record->setExteriorMeasurementDate($exteriorMeasurementDate);
            }
        }

        $em->persist($record);
        if($flush) { $em->flush(); }
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @throws \Exception
     */
    public static function cacheByAnimalIds(Connection $conn, $animalIds)
    {
        self::insertBlankResultTableRecordIfEmpty($conn, $animalIds, []);

        /*
         *  NOTE!
         *
         *  Predicate is not set nor used
         *  pmsg is not set nor used
         */

        AnimalGradesCacher::updateDutchBreedStatus($conn, $animalIds);
        NLingCacher::updateNLingValues($conn, $animalIds);
        ProductionCacher::updateProductionValues($conn, $animalIds);
        WeightCacher::updateWeights($conn, $animalIds);
        ExteriorCacher::updateExteriors($conn, $animalIds);
    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     */
    public static function deleteDuplicateAnimalCacheRecords(ObjectManager $em, $cmdUtil = null)
    {
        $hasDuplicates = true;
        $isFirstIteration = true;
        while($hasDuplicates) {

            $sql = "SELECT animal_id, MIN(id) as min_id FROM animal_cache
                GROUP BY animal_id HAVING COUNT(*) > 1";
            $results = $em->getConnection()->query($sql)->fetchAll();

            if($cmdUtil != null && $isFirstIteration){
                if(count($results) == 0) { return; }
                $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1);
                $isFirstIteration = false;
            }

            foreach ($results as $result) {
                $sql = "DELETE FROM animal_cache WHERE id = ".$result['min_id'];
                $em->getConnection()->exec($sql);

                if($cmdUtil != null){ $cmdUtil->advanceProgressBar(1); }
            }
            if(count($results) == 0) { $hasDuplicates = false; }
        }
        if($cmdUtil != null){ $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param boolean $flush
     */
    public static function cacheWeightByAnimal(ObjectManager $em, Animal $animal, $flush = true)
    {
        $animalId = $animal->getId();

        /** @var AnimalCacheRepository $repository */
        $repository = $em->getRepository(AnimalCache::class);
        /** @var AnimalCache $record */
        $record = $repository->findOneBy(['animalId' => $animalId]);

        if($record == null) {
            //If no record exists yet, create a new complete one. Not just the weight data.
            self::cacheByAnimal($em, $animal, $flush);

        } else {
            //Weight Data
            /** @var WeightRepository $weightRepository */
            $weightRepository = $em->getRepository(Weight::class);
            $lastWeightMeasurementData = $weightRepository->getLatestWeightBySql($animalId);
            $weight = $lastWeightMeasurementData[JsonInputConstant::WEIGHT];
            $isBirthWeight = $lastWeightMeasurementData[JsonInputConstant::IS_BIRTH_WEIGHT];
            $weightMeasurementDateString = $lastWeightMeasurementData[JsonInputConstant::MEASUREMENT_DATE];
            $weightExists = $weightMeasurementDateString != null;

            //If record already exists, only update the weight data
            $record->setLogDate(new \DateTime()); //update logDate
            $record->setLastWeight($weight);
            $record->setWeightMeasurementDateByDateString($weightMeasurementDateString);

            $em->persist($record);
            if($flush) { $em->flush(); }
        }

        WeightCacher::updateWeights($em->getConnection(), [$animalId]);
    }


    /**
     * @param ObjectManager $em
     * @param Litter $litter
     * @param bool $flush
     */
    public static function cacheBirthLitter(ObjectManager $em, Litter $litter, $flush = true)
    {
        $mother = $litter->getAnimalMother();
        if($mother instanceof Ewe) {
            self::cacheProductionByAnimal($em, $mother, $flush);
        }

        $father = $litter->getAnimalFather();
        if($father instanceof Ram) {
            self::cacheProductionByAnimal($em, $father, $flush);
        }

        foreach ($litter->getChildren() as $child) {
            self::cacheLitterOfBirthByAnimal($em, $child, $flush);
        }
    }


    /**
     * @param EntityManagerInterface $em
     * @param Animal $animal
     * @param boolean $flush
     */
    private static function cacheLitterOfBirthByAnimal(EntityManagerInterface $em, Animal $animal, $flush = true)
    {
        $animalId = $animal->getId();
        /** @var AnimalCacheRepository $repository */
        $repository = $em->getRepository(AnimalCache::class);
        /** @var AnimalCache $record */
        $record = $repository->findOneBy(['animalId' => $animalId]);

        if($record == null) {
            //If no record exists yet, create a new complete one. Not just the litter data.
            self::cacheByAnimal($em, $animal, $flush);

        } else {
            //Litter Data
            $nLing = self::getNLingData($em, $animal->getId());

            //If record already exists, only update the nLing data
            $record->setLogDate(new \DateTime()); //update logDate
            $record->setNLing($nLing);

            $em->persist($record);
            if($flush) { $em->flush(); }
        }

    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param boolean $flush
     */
    public static function cacheExteriorByAnimal(ObjectManager $em, Animal $animal, $flush = true)
    {
        $animalId = $animal->getId();
        /** @var AnimalCacheRepository $repository */
        $repository = $em->getRepository(AnimalCache::class);
        /** @var AnimalCache $record */
        $record = $repository->findOneBy(['animalId' => $animalId]);

        if($record == null) {

            //If no record exists yet, create a new complete one. Not just the exterior data.
            self::cacheByAnimal($em, $animal, $flush);

        } else {
            ExteriorCacher::updateExteriors($em->getConnection(), [$animalId]);
        }

    }


    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param boolean $flush
     */
    private static function cacheProductionByAnimal(ObjectManager $em, Animal $animal, $flush = true)
    {
        $animalId = $animal->getId();

        /** @var AnimalCacheRepository $repository */
        $repository = $em->getRepository(AnimalCache::class);
        /** @var AnimalCache $record */
        $record = $repository->findOneBy(['animalId' => $animalId]);

        if($record == null) {
            //If no record exists yet, create a new complete one. Not just the production data.
            self::cacheByAnimal($em, $animal, $flush);

        } else {
            //Production Data
            $productionString = self::generateProductionString($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString());
            $productionAge = ProductionUtil::getProductionAgeFromProductionString($productionString);
            $litterCount = ProductionUtil::getLitterCountFromProductionString($productionString);
            $totalOffspring = ProductionUtil::getTotalOffspringCountFromProductionString($productionString);
            $totalBornOffspring = ProductionUtil::getBornAliveCountFromProductionString($productionString);
            $hasOneYearMark = ProductionUtil::hasOneYearMark($productionString);
            $record->setProductionAge($productionAge);
            $record->setLitterCount($litterCount);
            $record->setTotalOffspringCount($totalOffspring);
            $record->setBornAliveOffspringCount($totalBornOffspring);
            $record->setGaveBirthAsOneYearOld($hasOneYearMark);

            //If record already exists, only update the production data
            $record->setLogDate(new \DateTime()); //update logDate

            $em->persist($record);
            if($flush) { $em->flush(); }
        }

    }



    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @param string $gender
     * @param string $dateOfBirthString
     * @return string
     */
    public static function generateProductionString(ObjectManager $em, $animalId, $gender, $dateOfBirthString)
    {
        /** @var LitterRepository $litterRepository */
        $litterRepository = $em->getRepository(Litter::class);

        //Litters of offspring, data for production string
        $offspringLitterData = $litterRepository->getAggregatedLitterDataOfOffspring($animalId); //data from the litter table

        $litterCount = $offspringLitterData[JsonInputConstant::LITTER_COUNT];
        $totalStillbornCount = $offspringLitterData[JsonInputConstant::TOTAL_STILLBORN_COUNT];
        $totalBornAliveCount = $offspringLitterData[JsonInputConstant::TOTAL_BORN_ALIVE_COUNT];
        $totalOffSpringCountByLitterData = $totalBornAliveCount + $totalStillbornCount;

        $earliestLitterDate = $offspringLitterData[JsonInputConstant::EARLIEST_LITTER_DATE];
        $latestLitterDate = $offspringLitterData[JsonInputConstant::LATEST_LITTER_DATE];
        if($earliestLitterDate != null) { $earliestLitterDate = new \DateTime($earliestLitterDate); }
        if($latestLitterDate != null) { $latestLitterDate = new \DateTime($latestLitterDate); }

        $dateOfBirthDateTime = null;
        if($dateOfBirthString != null) { $dateOfBirthDateTime = new \DateTime($dateOfBirthString); }

        return DisplayUtil::parseProductionString($dateOfBirthDateTime, $earliestLitterDate, $latestLitterDate, $litterCount, $totalOffSpringCountByLitterData, $totalBornAliveCount, $gender);
    }


    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @return string
     */
    public static function getNLingData(ObjectManager $em, $animalId)
    {
        /** @var LitterRepository $litterRepository */
        $litterRepository = $em->getRepository(Litter::class);

        //Litter in which animal was born
        $litterSize = $litterRepository->getLitterSize($animalId);
        return DisplayUtil::parseNLingString($litterSize);
    }


    /**
     * @param Connection $connection
     * @param array $animalIds
     * @param array $locationIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function insertBlankResultTableRecordIfEmpty(Connection $connection, $animalIds = [], $locationIds = [])
    {
        $filterString = SqlUtil::filterStringByAnimalIdsAndLocationIds($animalIds, $locationIds, 'a.id', 'l.id');
        if (empty($filterString)) {
            return 0;
        }

        $sql = "SELECT
                  a.id as ".JsonInputConstant::ANIMAL_ID."
                FROM animal a
                  LEFT JOIN animal_cache c ON c.animal_id = a.id
                WHERE c.id ISNULL AND ".$filterString;
        $result = $connection->query($sql)->fetchAll();

        if (empty($result)) {
            return 0;
        }

        $animalIdsToUpdate = array_map(function ($set) {
            return $set[JsonInputConstant::ANIMAL_ID];
        }, $result);

        $updateSql = 'INSERT INTO animal_cache (animal_id) VALUES '.SqlUtil::valueString($animalIdsToUpdate,false);
        return SqlUtil::updateWithCount($connection, $updateSql);
    }



}