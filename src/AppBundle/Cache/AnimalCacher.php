<?php

namespace AppBundle\Cache;

use AppBundle\Component\Utils;
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
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DisplayUtil;
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
    const UPDATE_BATCH_SIZE = 10000;

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
                        if(!array_key_exists($animalId, $cachedAnimalIds)) { //THIS PREVENTS DUPLICATES!
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
                        if(!array_key_exists($animalId, $cachedAnimalIds)) { //THIS PREVENTS DUPLICATES!
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
        $production = self::generateProductionStringBySql($em, $animalId);
        $nLing = self::getNLingData($em, $animalId);

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

        $record->setAnimalId($animalId);
        $record->setDutchBreedStatus($dutchBreedStatus);
        $record->setPredicate($predicate);
        $record->setNLing($nLing);
        $record->setProduction($production);
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
            $record->setExteriorMeasurementDateByDateString($exteriorMeasurementDateString);
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

            /** @var GeneticBaseRepository $geneticBaseRepository */
            $geneticBaseRepository = $em->getRepository(GeneticBase::class);
            $breedValuesYear = $geneticBaseRepository->getLatestYear();
            $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

            self::cacheById($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString(), $animal->getBreedType(), $breedValuesYear, $geneticBases, false, $flush);

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
            //If no record exists yet, create a new complete one. Not just the weight data.

            /** @var GeneticBaseRepository $geneticBaseRepository */
            $geneticBaseRepository = $em->getRepository(GeneticBase::class);
            $breedValuesYear = $geneticBaseRepository->getLatestYear();
            $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

            self::cacheById($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString(), $animal->getBreedType(), $breedValuesYear, $geneticBases, false, $flush);

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
    private static function cacheProductionByAnimal(ObjectManager $em, Animal $animal, $flush = true)
    {
        $animalId = $animal->getId();

        /** @var AnimalCacheRepository $repository */
        $repository = $em->getRepository(AnimalCache::class);
        /** @var AnimalCache $record */
        $record = $repository->findOneBy(['animalId' => $animalId]);

        if($record == null) {
            //If no record exists yet, create a new complete one. Not just the weight data.

            /** @var GeneticBaseRepository $geneticBaseRepository */
            $geneticBaseRepository = $em->getRepository(GeneticBase::class);
            $breedValuesYear = $geneticBaseRepository->getLatestYear();
            $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);

            self::cacheById($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString(), $animal->getBreedType(), $breedValuesYear, $geneticBases, false, $flush);

        } else {
            //Production Data
            $production = self::generateProductionStringBySql($em, $animalId);

            //If record already exists, only update the production data
            $record->setLogDate(new \DateTime()); //update logDate
            $record->setProduction($production);

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
     * @param $animalId
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function generateProductionStringBySql(ObjectManager $em, $animalId)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        if(!(is_int($animalId) && $animalId != 0)) { return DisplayUtil::EMPTY_PRODUCTION; }
        
        $sql = "SELECT DISTINCT (a.id), gender,
                  EXTRACT(YEAR FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                  ROUND(CAST(EXTRACT(MONTH FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION)/11) --add year if months >= 6
                    as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                    as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_father_id
                  WHERE date_of_birth NOTNULL AND a.id = ".$animalId."
                GROUP BY a.id
                
                UNION
                
                SELECT DISTINCT (a.id), gender,
                  EXTRACT(YEAR FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                  ROUND(CAST(EXTRACT(MONTH FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION)/11) --add year if months >= 6
                    as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                    as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_mother_id
                  WHERE date_of_birth NOTNULL AND a.id = ".$animalId."
                GROUP BY a.id
                
                UNION --Below when date of births are null
                
                SELECT DISTINCT (a.id), gender,
                  -1 as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                                          as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_father_id
                WHERE date_of_birth ISNULL AND a.id = ".$animalId."
                GROUP BY a.id
                
                UNION
                
                SELECT DISTINCT (a.id), gender,
                  -1 as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                                          as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_mother_id
                WHERE date_of_birth ISNULL AND a.id = ".$animalId."
                GROUP BY a.id";
        $result = $conn->query($sql)->fetch();
        return self::parseProductionStringFromSqlResult($result);
    }


    private static function parseProductionStringFromSqlResult($result)
    {
        $ageInNsfoSystem = intval($result['age_in_nsfo_system']) > 0 ? $result['age_in_nsfo_system'] : '-';
        $litterCount = $result['litter_count'];
        $totalBornCount = $result['total_born_count'];
        $bornAliveCount = $result['born_alive_count'];
        $oneYearMark = boolval($result['has_one_year_mark']) ? '*' : '';

        if($litterCount == null || $totalBornCount == null || $bornAliveCount == null) { return DisplayUtil::EMPTY_PRODUCTION; }

        return $ageInNsfoSystem.'/'.$litterCount.'/'.$totalBornCount.'/'.$bornAliveCount.$oneYearMark;
    }


    /**
     * Returns true if production string in the animal_cache was updated.
     *
     * @param ObjectManager $em
     * @param int $animalId
     * @return boolean
     */
    public static function updateProductionString(ObjectManager $em, $animalId)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        $sql = "SELECT date_of_birth, gender, c.production FROM animal a
                  LEFT JOIN animal_cache c ON c.animal_id = a.id
                WHERE a.id = ".$animalId;
        $result = $conn->query($sql)->fetch();
        $gender = $result['gender'];
        $dateOfBirthString = $result['date_of_birth'];
        $currentProductionString = $result['production'];

        if($currentProductionString == null) {
            //No animalCache exists that can be updated
            return false;

        } else {
            $productionString = self::generateProductionStringBySql($em, $animalId);
            if($currentProductionString != $productionString) {
                $sql = "UPDATE animal_cache SET production = '".$productionString."' WHERE animal_id = ".$animalId;
                $conn->exec($sql);
                return true;
            }
        }
        return false;
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
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param OutputInterface $output
     * @param int $batchSize
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateAllMismatchedNLingData(ObjectManager $em, CommandUtil $cmdUtil = null,
                                                        OutputInterface $output = null, $batchSize = 10000)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();
        
        $sql = "SELECT a.id as animal_id, CONCAT(l.born_alive_count + l.stillborn_count,'-ling') as n_ling_new 
                FROM animal a
                    INNER JOIN litter l ON a.litter_id = l.id
                    INNER JOIN animal_cache c ON c.animal_id = a.id
                WHERE cast(trim(trailing '-ling' FROM c.n_ling) as INT) <> (l.born_alive_count + l.stillborn_count)";
        $results =  $conn->query($sql)->fetchAll();

        $totalCount = count($results);
        
        if($totalCount == 0) {
            if($output != null) { $output->writeln('There is no mismatched n-ling data for existing litters in the cache!'); }
            return;
        }

        $toUpdateCount = 0;
        $updatedCount = 0;
        $loopCounter = 0;

        $updateString = '';

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($totalCount, 1); }

        foreach ($results as $result) {
            $animalId = $result['animal_id'];
            $nLingNew = $result['n_ling_new'];

            $updateString = $updateString."('".$nLingNew."',".$animalId."),";
            $toUpdateCount++;
            $loopCounter++;

            //Update fathers
            if(($totalCount == $loopCounter //at end of loop
                    || ($toUpdateCount%$batchSize == 0 && $toUpdateCount != 0) //at end of batch
                ) && $updateString != '') //but never when there is nothing to update
            {
                $updateString = rtrim($updateString, ',');
                $sql = "UPDATE animal_cache as a SET n_ling = c.new_n_ling
				FROM (VALUES ".$updateString.") as c(new_n_ling, animal_id) WHERE c.animal_id = a.animal_id ";
                $conn->exec($sql);
                //Reset batch values
                $updateString = '';
                $updatedCount += $toUpdateCount;
                $toUpdateCount = 0;
            }

            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'n-ling in cache with litter updated|toUpdate: '.$updatedCount.'|'.$toUpdateCount); }
        }

        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }

    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param OutputInterface $output
     * @param int $batchSize
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateNonZeroNLingInCacheWithoutLitter(ObjectManager $em, CommandUtil $cmdUtil = null,
                                                        OutputInterface $output = null, $batchSize = 10000)
    {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        $sql = "SELECT a.id as animal_id
                FROM animal a
                  LEFT JOIN litter l ON a.litter_id = l.id
                  INNER JOIN animal_cache c ON c.animal_id = a.id
                WHERE l.id ISNULL AND c.n_ling <> '0-ling'";
        $results =  $conn->query($sql)->fetchAll();

        $totalCount = count($results);

        if($totalCount == 0) {
            if($output != null) { $output->writeln('There is no non-zero n-ling data without litters in the cache!'); }
            return;
        }

        $toUpdateCount = 0;
        $updatedCount = 0;
        $loopCounter = 0;

        $updateString = '';

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($totalCount, 1); }

        foreach ($results as $result) {
            $animalId = $result['animal_id'];

            $updateString = $updateString."('0-ling',".$animalId."),";
            $toUpdateCount++;
            $loopCounter++;

            //Update fathers
            if(($totalCount == $loopCounter //at end of loop
                    || ($toUpdateCount%$batchSize == 0 && $toUpdateCount != 0) //at end of batch
                ) && $updateString != '') //but never when there is nothing to update
            {
                $updateString = rtrim($updateString, ',');
                $sql = "UPDATE animal_cache as a SET n_ling = c.n_ling
				FROM (VALUES ".$updateString.") as c(n_ling, animal_id) WHERE c.animal_id = a.animal_id ";
                $conn->exec($sql);
                //Reset batch values
                $updateString = '';
                $updatedCount += $toUpdateCount;
                $toUpdateCount = 0;
            }

            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'non-zero n-ling without litter in cache updated|toUpdate: '.$updatedCount.'|'.$toUpdateCount); }
        }

        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }

    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil|null $cmdUtil
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateAllMismatchedProductionStrings(ObjectManager $em, CommandUtil $cmdUtil = null) {
        /** @var Connection $conn */
        $conn = $em->getConnection();

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(3, 1, 'Generate searchArray: cachedProductionValues'); }

        //Get Data and create searchArrays
        $sql = "SELECT a.id as animal_id, c.production,
                  split_part(c.production,'/',1) as age_in_nsfo_system,
                  split_part(c.production,'/',2) as litter_count,
                  split_part(c.production,'/',3) as total_born_count,
                  substring(split_part(c.production,'/',4), 1, length(split_part(c.production,'/',4))-1) as born_alive_count,
                  true as has_one_year_mark
                FROM animal a
                INNER JOIN animal_cache c ON c.animal_id = a.id
                WHERE right(c.production, 1) = '*'
                UNION
                SELECT a.id as animal_id, c.production,
                  split_part(c.production,'/',1) as age_in_nsfo_system,
                  split_part(c.production,'/',2) as litter_count,
                  split_part(c.production,'/',3) as total_born_count,
                  split_part(c.production,'/',4) as born_alive_count,
                  false as has_one_year_mark
                FROM animal a
                  INNER JOIN animal_cache c ON c.animal_id = a.id
                WHERE right(c.production, 1) <> '*'";
        $results = $conn->query($sql)->fetchAll();

        $cachedProductionValues = [];
        foreach ($results as $result) {
            $animalId = $result['animal_id'];
            $cachedProductionValues[$animalId] = $result;
        }

        if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'Generate searchArray: generatedProductionValues'); }

        $sql = "SELECT DISTINCT (a.id) as animal_id, gender,
                  EXTRACT(YEAR FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                  ROUND(CAST(EXTRACT(MONTH FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION)/11) --add year if months >= 6
                    as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                    as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_father_id
                  WHERE date_of_birth NOTNULL
                GROUP BY a.id
                
                UNION
                
                SELECT DISTINCT (a.id) as animal_id, gender,
                  EXTRACT(YEAR FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) + --get years
                  ROUND(CAST(EXTRACT(MONTH FROM AGE (MAX(l.litter_date), MAX(a.date_of_birth))) AS DOUBLE PRECISION)/11) --add year if months >= 6
                    as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                    as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_mother_id
                  WHERE date_of_birth NOTNULL
                GROUP BY a.id
                
                UNION --Below when date of births are null
                
                SELECT DISTINCT (a.id) as animal_id, gender,
                  -1 as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                                          as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_father_id
                WHERE date_of_birth ISNULL
                GROUP BY a.id
                
                UNION
                
                SELECT DISTINCT (a.id) as animal_id, gender,
                  -1 as age_in_nsfo_system,
                  COUNT(l.id) as litter_count,
                  SUM(l.born_alive_count) + SUM(l.stillborn_count) as total_born_count,
                  SUM(l.born_alive_count) as born_alive_count,
                
                  EXTRACT(YEAR FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth)))*12 + --get all as months
                  EXTRACT(MONTH FROM AGE (MIN(l.litter_date), MAX(a.date_of_birth))) <= 18 AND a.gender = 'FEMALE'
                                          as has_one_year_mark
                
                FROM animal a
                  INNER JOIN litter l ON a.id = l.animal_mother_id
                WHERE date_of_birth ISNULL
                GROUP BY a.id";
        $results = $conn->query($sql)->fetchAll();

        $generatedProductionValues = [];
        foreach ($results as $result) {
            $animalId = $result['animal_id'];
            $generatedProductionValues[$animalId] = $result;
        }

        if($cmdUtil != null) {
            $cmdUtil->setProgressBarMessage('SearchArrays Complete!');
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        }
        

        //Update changed litterData
        $totalCount = count($cachedProductionValues);
        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($totalCount, 1); }

        $updateString = '';
        $loopCount = 0;
        $inBatchCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;

        $animalIdsInCache = array_keys($cachedProductionValues);
        foreach($animalIdsInCache as $animalId) {
            $loopCount++;

            $cachedProductionValue = $cachedProductionValues[$animalId];
            $generatedProductionValue = Utils::getNullCheckedArrayValue($animalId, $generatedProductionValues);

            $generatedProductionString = $generatedProductionValue == null ? DisplayUtil::EMPTY_PRODUCTION : self::parseProductionStringFromSqlResult($generatedProductionValue);
            $cachedProductionString = $cachedProductionValue['production'];

            if($cachedProductionString == $generatedProductionString) {
                $unchangedCount++;
            } else {
                $updateString = $updateString."('".$generatedProductionString."',".$animalId.'),';
                $inBatchCount++;
            }

            if(($loopCount == $totalCount && $updateString != '')
                || ($inBatchCount%self::UPDATE_BATCH_SIZE == 0 && $inBatchCount != 0)) {
                $updateString = rtrim($updateString, ',');
                $sql = "UPDATE animal_cache as c SET production = v.production
						FROM (VALUES ".$updateString."
							 ) as v(production, animal_id) WHERE c.animal_id = v.animal_id";
                $conn->exec($sql);
                //Reset batch string and counters
                $updateString = '';
                $updatedCount += $inBatchCount;
                $inBatchCount = 0;
            }

            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'Production in animalCache processed|inBatch|noChange: '.$updatedCount.'|'.$inBatchCount.'|'.$unchangedCount); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
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
}