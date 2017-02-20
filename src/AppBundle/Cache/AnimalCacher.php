<?php

namespace AppBundle\Cache;

use AppBundle\Constant\BreedValueLabel;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalCache;
use AppBundle\Entity\AnimalCacheRepository;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\BreedValuesSetRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\GeneticBase;
use AppBundle\Entity\GeneticBaseRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\ProductionUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use \Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class AnimalCacher
{
    const GENERAL_NULL_FILLER = '-';
    const EMPTY_DATE_OF_BIRTH = '-';
    const NEUTER_STRING = '-';
    const EMPTY_INDEX_VALUE = '-/-';
    const FLUSH_BATCH_SIZE = 1000;

    //Cache setting
    const CHECK_ANIMAL_CACHE_BEFORE_PERSISTING = true;


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

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

        $count = 0;

        //Get ids of already cached animals
        $sql = "SELECT animal_id FROM animal_cache";
        $results = $em->getConnection()->query($sql)->fetchAll();
        $cachedAnimalIds = [];
        foreach ($results as $result) {
            $animalId = intval($result['animal_id']);
            $cachedAnimalIds[$animalId] = $animalId;
        }

        if($cmdUtil instanceof CommandUtil) {
            $cmdUtil->setStartTimeAndPrintIt(count($animalCacherInputData) + 1, 1, 'Generating animal cache records');
            foreach ($animalCacherInputData as $record) {
                $animalId = $record['animal_id'];
                if(!array_key_exists($animalId, $cachedAnimalIds) || !$ignoreAnimalsWithAnExistingCache) { //Double checks for duplicates
                    self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $record['animal_cache_id'] != null, $flushPerRecord);
                }
                if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
                $cmdUtil->advanceProgressBar(1);
            }
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        } else {
            foreach ($animalCacherInputData as $record) {
                $animalId = $record['animal_id'];
                if(!array_key_exists($animalId, $cachedAnimalIds) || !$ignoreAnimalsWithAnExistingCache) { //Double checks for duplicates
                    self::cacheById($em, $record['animal_id'], $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $record['animal_cache_id'] != null, $flushPerRecord);
                }
                if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
            }
        }

        if(!$flushPerRecord) { $em->flush(); }

        //DuplicateCheck
        self::deleteDuplicateAnimalCacheRecords($em);
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

        //Get ids of already cached animals
        $sql = "SELECT animal_id FROM animal_cache";
        $results = $em->getConnection()->query($sql)->fetchAll();
        $cachedAnimalIds = [];
        foreach ($results as $result) {
            $animalId = intval($result['animal_id']);
            $cachedAnimalIds[$animalId] = $animalId;
        }

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

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
                            self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $record['animal_cache_id'] != null, $flushPerRecord);

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
                            self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $record['animal_cache_id'] != null, $flushPerRecord);

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
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param int $locationId
     */
    public static function cacheAnimalsBySqlInsert(ObjectManager $em, CommandUtil $cmdUtil = null, $locationId = null)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $em->getRepository(AnimalCache::class);
        $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataPerLocation(true, null, $locationId);
        $totalCount = count($animalCacherInputData);
        if($totalCount == 0) { return; }

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

        //Get ids of already cached animals
        $sql = "SELECT animal_id FROM animal_cache";
        $results = $conn->query($sql)->fetchAll();
        $cachedAnimalIds = [];
        foreach ($results as $result) {
            $animalId = intval($result['animal_id']);
            $cachedAnimalIds[$animalId] = $animalId;
        }
        

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
                $insertString = $insertString . self::getCacheByIdInsertString($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $maxAnimalCacheId).',';
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
    }


    private static function insertByBatch(Connection $conn, $insertString)
    {
        $insertString = rtrim($insertString, ',');
        $sql = "INSERT INTO animal_cache (id, log_date, animal_id, dutch_breed_status,
						  n_ling, production_age, litter_count, total_offspring_count, born_alive_offspring_count, gave_birth_as_one_year_old, breed_value_growth, breed_value_muscle_thickness, 
						  breed_value_fat, lamb_meat_index, lamb_meat_index_without_accuracy, last_weight, weight_measurement_date, kind, skull, muscularity, proportion, progress,
						  exterior_type, leg_work, fur, general_appearance, height, breast_depth,
						  torso_length, markings, exterior_measurement_date						  
						)VALUES ".$insertString;
        $conn->exec($sql);
    }

    
    private static function getCacheByIdInsertString(ObjectManager $em, $animalId, $gender, $dateOfBirthString, $breedType, $breedValuesYear, $geneticBases, $maxAnimalCacheId)
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

        //Breed Values
        $breedValuesArray = self::getUnformattedBreedValues($em, $animalId, $breedValuesYear, $geneticBases);
        $lambMeatIndexAccuracy = $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX_ACCURACY];
        //NOTE! Only include the lambIndexValue if the accuracy is at least the MIN accuracy required
        $lambMeatIndexWithoutAccuracy = 'NULL';
        if($lambMeatIndexAccuracy >= BreedValueUtil::MIN_LAMB_MEAT_INDEX_ACCURACY) {
            $lambMeatIndexWithoutAccuracy = SqlUtil::getNullCheckedValueForSqlQuery($breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX],true);
        }
        $formattedBreedValues = BreedValueUtil::getFormattedBreedValues($breedValuesArray);

        $breedValueGrowth = SqlUtil::getNullCheckedValueForSqlQuery($formattedBreedValues[BreedValueLabel::GROWTH],true);
        $breedValueMuscleThickness = SqlUtil::getNullCheckedValueForSqlQuery($formattedBreedValues[BreedValueLabel::MUSCLE_THICKNESS],true);
        $breedValueFat = SqlUtil::getNullCheckedValueForSqlQuery($formattedBreedValues[BreedValueLabel::FAT],true);
        $lambMeatIndex = SqlUtil::getNullCheckedValueForSqlQuery(self::getFormattedLambMeatIndexWithAccuracy($breedValuesArray),true);

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
            .",".$breedValueGrowth.",".$breedValueMuscleThickness
            .",".$breedValueFat.",".$lambMeatIndex.",".$lambMeatIndexWithoutAccuracy.",".$weight.",".$weightMeasurementDateString
            .",".$kind.",".$skull.",".$muscularity.",".$proportion.",".$progress.",".$exteriorType.",".$legWork.",".$fur
            .",".$generalAppearance.",".$height.",".$breastDepth.",".$torsoLength.",".$markings.",".$exteriorMeasurementDateString.")";
    }

    /**
     * @param ObjectManager $em
     * @param Animal $animal
     * @param bool $flush
     */
    public static function cacheByAnimal(ObjectManager $em, Animal $animal, $flush = true) {
        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);
        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

        $animalId = $animal->getId();
        if($animalId != null) {
            self::cacheById($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString(), $animal->getBreedType(), $breedValuesYear, $geneticBases, false, $flush);
        }
    }

    /**
     * @param ObjectManager $em
     * @param int $animalId
     * @param string $gender
     * @param string $dateOfBirthString
     * @param string $breedType
     * @param int $breedValuesYear
     * @param GeneticBase $geneticBases
     * @param boolean $isUpdate
     * @param boolean $flush
     */
    public static function cacheById(ObjectManager $em, $animalId, $gender, $dateOfBirthString, $breedType, $breedValuesYear, $geneticBases, $isUpdate, $flush = true)
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

        //Breed Values
        $breedValuesArray = self::getUnformattedBreedValues($em, $animalId, $breedValuesYear, $geneticBases);
        $lambMeatIndexAccuracy = $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX_ACCURACY];
        //NOTE! Only include the lambIndexValue if the accuracy is at least the MIN accuracy required
        $lambMeatIndexWithoutAccuracy = null;
        if($lambMeatIndexAccuracy >= BreedValueUtil::MIN_LAMB_MEAT_INDEX_ACCURACY) {
            $lambMeatIndexWithoutAccuracy = $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX];
        }
        $formattedBreedValues = BreedValueUtil::getFormattedBreedValues($breedValuesArray);

        $breedValueGrowth = $formattedBreedValues[BreedValueLabel::GROWTH];
        $breedValueMuscleThickness = $formattedBreedValues[BreedValueLabel::MUSCLE_THICKNESS];
        $breedValueFat = $formattedBreedValues[BreedValueLabel::FAT];
        $lambMeatIndex = self::getFormattedLambMeatIndexWithAccuracy($breedValuesArray);

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
        $breedValueLitterSize = null;
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
        $record->setBreedValueLitterSize($breedValueLitterSize);
        $record->setBreedValueGrowth($breedValueGrowth);
        $record->setBreedValueMuscleThickness($breedValueMuscleThickness);
        $record->setBreedValueFat($breedValueFat);
        $record->setLambMeatIndex($lambMeatIndex);
        $record->setLambMeatIndexWithoutAccuracy($lambMeatIndexWithoutAccuracy);
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
     * @param ObjectManager $em
     * @param Animal $animal
     * @param boolean $flush
     */
    private static function cacheLitterOfBirthByAnimal(ObjectManager $em, Animal $animal, $flush = true)
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
            AnimalCacher::updateExteriors($em->getConnection(), [$animalId]);
        }

    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllExteriors(Connection $conn){
        return self::updateExteriors($conn, null);
    }


    /**
     * $animalIds == null: all exterior values in animalCache are updated
     * $animalIds count == 0; nothing is updated
     * $animalIds count > 0: only given animalIds are updated
     *
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     */
    public static function updateExteriors(Connection $conn, $animalIds)
    {
        $updateCount = 0;
        
        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'xx.animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }


        $sql = "WITH rows AS (
                UPDATE animal_cache SET
                  skull = v.skull,
                  muscularity = v.muscularity,
                  proportion = v.proportion,
                  exterior_type = v.exterior_type,
                  leg_work = v.leg_work,
                  fur = v.fur,
                  general_appearance = v.general_appearance,
                  height = v.height,
                  breast_depth = v.breast_depth,
                  torso_length = v.torso_length,
                  markings = v.markings,
                  kind = v.kind,
                  progress = v.progress,
                  exterior_measurement_date = v.measurement_date,
                  log_date = '".TimeUtil::getTimeStampNow()."'
                FROM (
                  SELECT x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work, x.fur, x.general_appearance,
                    x.height, x.breast_depth, x.torso_length, x.markings, x.kind, x.progress, m.measurement_date
                  FROM exterior x
                  INNER JOIN measurement m ON x.id = m.id
                  INNER JOIN (
                    SELECT animal_id, MAX(measurement_date) as max_measurement_date
                    FROM exterior xx
                      INNER JOIN measurement mm ON xx.id = mm.id
                      WHERE mm.is_active = TRUE".$animalIdFilterString."
                    GROUP BY animal_id
                  ) AS last ON last.animal_id = x.animal_id AND m.measurement_date = last.max_measurement_date
                  INNER JOIN animal_cache c ON c.animal_id = x.animal_id
                  INNER JOIN animal a ON x.animal_id = a.id
                  WHERE (
                    c.skull ISNULL OR c.skull <> x.skull OR
                    c.muscularity ISNULL OR c.muscularity <> x.muscularity OR
                    c.proportion ISNULL OR c.proportion <> x.proportion OR
                    c.exterior_type ISNULL OR c.exterior_type <> x.exterior_type OR
                    c.leg_work ISNULL OR c.leg_work <> x.leg_work OR
                    c.fur ISNULL OR c.fur <> x.fur OR
                    c.general_appearance ISNULL OR c.general_appearance <> x.general_appearance OR
                    c.height ISNULL OR c.height <> x.height OR
                    c.breast_depth ISNULL OR c.breast_depth <> x.breast_depth OR
                    c.torso_length ISNULL OR c.torso_length <> x.torso_length OR
                    c.markings ISNULL OR c.markings <> x.markings OR
                    c.kind <> x.kind OR (c.kind ISNULL AND x.kind NOTNULL) OR
                    c.progress ISNULL OR c.progress <> x.progress OR
                    c.exterior_measurement_date ISNULL OR c.exterior_measurement_date <> m.measurement_date
                  )
                       -- AND a.location_id = 00000 < filter location_id here when necessary
                ) AS v(animal_id, skull, muscularity, proportion, exterior_type, leg_work, fur, general_appearance,
                height, breast_depth, torso_length, markings, kind, progress, measurement_date) WHERE animal_cache.animal_id = v.animal_id
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllWeights(Connection $conn){
        return self::updateWeights($conn, null);
    }


    /**
     * @param Connection $conn
     * @param $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateWeights(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'ww.animal_id').")";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sql = "WITH rows AS (
                  UPDATE animal_cache SET
                    last_weight = v.last_weight,
                    weight_measurement_date = v.weight_measurement_date,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                  FROM (
                         SELECT w.animal_id, w.weight, m.measurement_date
                         FROM weight w
                           INNER JOIN measurement m ON w.id = m.id
                           INNER JOIN (
                                        SELECT animal_id, MAX(measurement_date) as max_measurement_date,
                                          MAX(log_date) as max_log_date
                                        FROM weight ww
                                          INNER JOIN measurement mm ON ww.id = mm.id
                                          --Remove is_revoked if column data is moved to is_active and variable is removed
                                        WHERE ww.is_revoked = FALSE".$animalIdFilterString." --AND mm.is_active = TRUE
                                        GROUP BY animal_id
                       ) AS last ON last.animal_id = w.animal_id AND m.measurement_date = last.max_measurement_date
                           AND m.log_date = last.max_log_date
                  INNER JOIN animal_cache c ON c.animal_id = w.animal_id
                  INNER JOIN animal a ON w.animal_id = a.id
                  WHERE (
                  c.last_weight ISNULL OR c.last_weight <> w.weight OR
                  c.weight_measurement_date ISNULL OR c.weight_measurement_date <> m.measurement_date
                  )
                  -- AND a.location_id = 00000 < filter location_id here when necessary
                ) AS v(animal_id, last_weight, weight_measurement_date) WHERE animal_cache.animal_id = v.animal_id
                RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
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
     * @param int $animalId
     * @param int $breedValuesYear
     * @param GeneticBase $geneticBases
     * @param ObjectManager $em
     * @return array
     */
    private static function getUnformattedBreedValues($em, $animalId, $breedValuesYear = null, $geneticBases = null)
    {
        /** @var BreedValuesSetRepository $breedValuesSetRepository */
        $breedValuesSetRepository = $em->getRepository(BreedValuesSet::class);
        return $breedValuesSetRepository->getBreedValuesCorrectedByGeneticBaseWithAccuracies($animalId, $breedValuesYear, $geneticBases);
    }


    /**
     * @param array $breedValuesArray
     * @return string
     */
    public static function getFormattedLambMeatIndexWithAccuracy($breedValuesArray)
    {
        return BreedValueUtil::getFormattedLamMeatIndexWithAccuracy(
            $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX],
            $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX_ACCURACY],
            self::EMPTY_INDEX_VALUE);
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllProductionValues(Connection $conn)
    {
        return self::updateProductionValues($conn, '');
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateProductionValues(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'a.id').") ";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sql = "WITH rows AS (
                  UPDATE animal_cache
                  SET
                    production_age             = v.production_age,
                    litter_count               = v.litter_count,
                    total_offspring_count      = v.total_born_count,
                    born_alive_offspring_count = v.born_alive_count,
                    gave_birth_as_one_year_old = v.gave_birth_as_one_year_old,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                  FROM (
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                           ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION) /
                                 11) --add year if months >= 6
                                                                            AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                           FALSE                                            AS has_one_year_mark,
                           --fathers never get a one-year-mark
                           (
                             MAX(c.production_age) <> EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                                                      ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS
                                                                 DOUBLE PRECISION) / 11) --add year if months >= 6
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <> FALSE
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_father_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth NOTNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                
                         UNION
                
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                           ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION) /
                                 11) --add year if months >= 6
                                                                            AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                
                           EXTRACT(YEAR FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) * 12 + --get all as months
                           EXTRACT(MONTH FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                                                                            AS has_one_year_mark,
                           (
                             MAX(c.production_age) <> EXTRACT(YEAR FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                                                      ROUND(CAST(EXTRACT(MONTH FROM AGE(MAX(l.litter_date), MAX(a.date_of_birth))) AS
                                                                 DOUBLE PRECISION) / 11) --add year if months >= 6
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <>
                                (EXTRACT(YEAR FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) * 12 + --get all as months
                                 EXTRACT(MONTH FROM AGE(MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE')
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_mother_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth NOTNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                         UNION --Below when date of births are null
                
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           0                                                AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                           FALSE                                            AS has_one_year_mark,
                           --fathers never get a one-year-mark
                           (
                             MAX(c.production_age) <> 0
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <> FALSE
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_father_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth ISNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                
                         UNION
                
                         SELECT DISTINCT
                           (a.id)                                           AS animal_id,
                           MAX(c.id)                                        AS animal_cache_id,
                           0                                                AS age_in_nsfo_system,
                           COUNT(l.id)                                      AS litter_count,
                           SUM(l.born_alive_count) + SUM(l.stillborn_count) AS total_born_count,
                           SUM(l.born_alive_count)                          AS born_alive_count,
                           FALSE                                            AS has_one_year_mark,
                           --dateOfBirth missing, cannot calculate this value
                           (
                             MAX(c.production_age) <> 0
                             OR MAX(c.litter_count) <> COUNT(l.id)
                             OR MAX(c.total_offspring_count) <> SUM(l.born_alive_count) + SUM(l.stillborn_count)
                             OR MAX(c.born_alive_offspring_count) <> SUM(l.born_alive_count)
                             OR BOOL_AND(c.gave_birth_as_one_year_old) <> FALSE
                             OR MAX(c.production_age) ISNULL OR MAX(c.litter_count) ISNULL OR MAX(c.total_offspring_count) ISNULL OR
                             MAX(c.born_alive_offspring_count) ISNULL
                           )                                                AS update_production
                
                         FROM animal a
                           INNER JOIN litter l ON a.id = l.animal_mother_id
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                         WHERE date_of_birth ISNULL AND l.status <> '".RequestStateType::REVOKED."' ".$animalIdFilterString."
                         GROUP BY a.id
                         
                         UNION

                         SELECT
                           a.id    AS animal_id,
                           g.animal_cache_id AS animal_cache_id,
                           NULL      AS age_in_nsfo_system,
                           NULL      AS litter_count,
                           NULL      AS total_born_count,
                           NULL      AS born_alive_count,
                           FALSE     AS has_one_year_mark,
                           TRUE      AS update_production
                         FROM animal a
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                           INNER JOIN (
                                        SELECT DISTINCT
                                          (a.id)    AS animal_id,
                                          MAX(c.id) AS animal_cache_id,
                                          COUNT(animal_father_id) = 0
                                          AND (
                                            MAX(c.production_age) NOTNULL OR
                                            MAX(c.litter_count) NOTNULL OR
                                            MAX(c.total_offspring_count) NOTNULL OR
                                            MAX(c.born_alive_offspring_count) NOTNULL OR
                                            BOOL_AND(c.gave_birth_as_one_year_old) = TRUE
                                          ) AS update_production
                                        FROM animal a
                                          INNER JOIN animal_cache c ON c.animal_id = a.id
                                          LEFT JOIN
                                          ( SELECT * FROM litter WHERE status <> '".RequestStateType::REVOKED."'
                                          )l ON l.animal_father_id = a.id
                                        WHERE a.type = 'Ram' ".$animalIdFilterString."
                                        GROUP BY a.id
                                      )g ON g.animal_id = a.id
                         WHERE g.update_production = TRUE
                
                         UNION
                
                         SELECT
                           a.id    AS animal_id,
                           g.animal_cache_id AS animal_cache_id,
                           NULL      AS age_in_nsfo_system,
                           NULL      AS litter_count,
                           NULL      AS total_born_count,
                           NULL      AS born_alive_count,
                           FALSE     AS has_one_year_mark,
                           TRUE      AS update_production
                         FROM animal a
                           INNER JOIN animal_cache c ON c.animal_id = a.id
                           INNER JOIN (
                                        SELECT DISTINCT
                                          (a.id)    AS animal_id,
                                          MAX(c.id) AS animal_cache_id,
                                          COUNT(animal_mother_id) = 0
                                          AND (
                                            MAX(c.production_age) NOTNULL OR
                                            MAX(c.litter_count) NOTNULL OR
                                            MAX(c.total_offspring_count) NOTNULL OR
                                            MAX(c.born_alive_offspring_count) NOTNULL OR
                                            BOOL_AND(c.gave_birth_as_one_year_old) = TRUE
                                          ) AS update_production
                                        FROM animal a
                                          INNER JOIN animal_cache c ON c.animal_id = a.id
                                          LEFT JOIN
                                          ( SELECT * FROM litter WHERE status <> '".RequestStateType::REVOKED."'
                                          )l ON l.animal_mother_id = a.id
                                        WHERE a.type = 'Ewe' ".$animalIdFilterString."
                                        GROUP BY a.id
                                      )g ON g.animal_id = a.id
                         WHERE g.update_production = TRUE
                         
                       ) AS v(animal_id, animal_cache_id, production_age, litter_count, total_born_count, born_alive_count, gave_birth_as_one_year_old, update_production)
                  WHERE v.update_production = TRUE AND animal_cache.id = v.animal_cache_id
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @return int
     */
    public static function updateAllNLingValues(Connection $conn)
    {
        return self::updateNLingValues($conn, '');
    }


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateNLingValues(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND (".SqlUtil::getFilterStringByIdsArray($animalIds,'a.id').") ";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sql = "WITH rows AS (
                  UPDATE animal_cache
                SET n_ling = v.new_n_ling,
                    log_date = '".TimeUtil::getTimeStampNow()."'
                FROM (
                       -- nLing, litter still linked and not revoked
                       SELECT c.id as cache_id, c.n_ling as current_n_ling, CONCAT(l.born_alive_count + l.stillborn_count,'-ling') as new_n_ling,
                         (c.n_ling <> CONCAT(l.born_alive_count + l.stillborn_count,'-ling') OR c.n_ling ISNULL ) as update_n_ling
                       FROM animal a
                         INNER JOIN litter l ON a.litter_id = l.id
                         INNER JOIN animal_cache c ON c.animal_id = a.id
                       WHERE (l.status <> 'REVOKED' AND l.animal_mother_id NOTNULL)
                             AND (c.n_ling <> CONCAT(l.born_alive_count + l.stillborn_count,'-ling') OR c.n_ling ISNULL )
                             ".$animalIdFilterString."
                       UNION
                       -- nLing, litter still linked but revoked or mother not set
                       SELECT c.id as cache_id, c.n_ling as current_n_ling, '0-ling' as new_n_ling,
                         (c.n_ling <> '0-ling' OR c.n_ling ISNULL ) as update_n_ling
                       FROM animal a
                         INNER JOIN litter l ON a.litter_id = l.id
                         INNER JOIN animal_cache c ON c.animal_id = a.id
                       WHERE (l.status = 'REVOKED' OR l.animal_mother_id ISNULL) --If mother ISNULL the offspringCounts <> nLing
                             AND (c.n_ling <> '0-ling'  OR c.n_ling ISNULL ) --the default value for unknown nLings should be '0-ling'
                             ".$animalIdFilterString."
                       UNION
                       -- nLing, litter not linked anymore
                       SELECT c.id as cache_id, c.n_ling as current_n_ling, '0-ling' as new_n_ling,
                         (c.n_ling <> '0-ling' OR c.n_ling ISNULL ) as update_n_ling
                       FROM animal a
                         LEFT JOIN litter l ON a.litter_id = l.id
                         INNER JOIN animal_cache c ON c.animal_id = a.id
                       WHERE l.id ISNULL
                             AND (c.n_ling <> '0-ling'  OR c.n_ling ISNULL ) --the default value for unknown nLings should be '0-ling'
                             ".$animalIdFilterString."
                     ) AS v(cache_id, current_n_ling, new_n_ling, update_n_ling) WHERE animal_cache.id = v.cache_id AND v.update_n_ling = TRUE
                  RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows";
        $updateCount = $conn->query($sql)->fetch()['count'];
        return $updateCount;
    }
}