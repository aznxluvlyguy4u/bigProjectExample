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
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DisplayUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use \Doctrine\Common\Persistence\ObjectManager;

class AnimalCacher
{
    const GENERAL_NULL_FILLER = '-';
    const EMPTY_DATE_OF_BIRTH = '-';
    const NEUTER_STRING = '-';
    const EMPTY_INDEX_VALUE = '-/-';
    const FLUSH_BATCH_SIZE = 1000;


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

        /** @var GeneticBaseRepository $geneticBaseRepository */
        $geneticBaseRepository = $em->getRepository(GeneticBase::class);

        $breedValuesYear = $geneticBaseRepository->getLatestYear();
        $geneticBases = $geneticBaseRepository->getNullCheckedGeneticBases($breedValuesYear);
        
        /** @var AnimalCacheRepository $animalCacheRepository */
        $animalCacheRepository = $em->getRepository(AnimalCache::class);
        $animalCacherInputData = $animalCacheRepository->getAnimalCacherInputDataPerLocation($ignoreAnimalsWithAnExistingCache, $ignoreCacheBeforeDateString, $locationId);

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
                if(!array_key_exists($animalId, $cachedAnimalIds)) { //Double checks for duplicates
                    self::cacheById($em, $animalId, $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $record['animal_cache_id'] != null, $flushPerRecord);
                }
                if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
                $cmdUtil->advanceProgressBar(1);
            }
            $cmdUtil->setEndTimeAndPrintFinalOverview();
        } else {
            foreach ($animalCacherInputData as $record) {
                $animalId = $record['animal_id'];
                if(!array_key_exists($animalId, $cachedAnimalIds)) { //Double checks for duplicates
                    self::cacheById($em, $record['animal_id'], $record['gender'], $record['date_of_birth'], $record['breed_type'], $breedValuesYear, $geneticBases, $record['animal_cache_id'] != null, $flushPerRecord);
                }
                if($count++%self::FLUSH_BATCH_SIZE == 0) { $em->flush(); }
            }
        }

        if(!$flushPerRecord) { $em->flush(); }
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
        $production = self::generateProductionString($em, $animalId, $gender, $dateOfBirthString);
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

        if($isUpdate) {
            /** @var AnimalCacheRepository $repository */
            $repository = $em->getRepository(AnimalCache::class);
            $record = $repository->findOneBy(['animalId' => $animalId]);
            if($record == null) { $record = new AnimalCache(); }
            $record->setLogDate(new \DateTime()); //update logDate
        } else {
            //New record
            $record = new AnimalCache();
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
        $sql = "SELECT animal_id FROM animal_cache
                GROUP BY animal_id HAVING COUNT(*) > 1";
        $results = $em->getConnection()->query($sql)->fetchAll();

        if($cmdUtil != null){ $cmdUtil->setStartTimeAndPrintIt(count($results)+1, 1); }

        foreach ($results as $result) {
            $sql = "DELETE FROM animal_cache WHERE animal_id = ".$result['animal_id'];
            $em->getConnection()->exec($sql);

            if($cmdUtil != null){ $cmdUtil->advanceProgressBar(1); }
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
            $production = self::generateProductionString($em, $animalId, $animal->getGender(), $animal->getDateOfBirthString());

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
}