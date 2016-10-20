<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\BreedValueLabel;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportFormat;
use AppBundle\Constant\ReportLabel;
use AppBundle\Constant\TwigCode;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\BreedValuesSet;
use AppBundle\Entity\BreedValuesSetRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\GeneticBase;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\NormalDistribution;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StarValueUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Translation;
use AppBundle\Util\TwigOutputUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class PedigreeCertificate
{
    const MAX_LENGTH_FULL_NAME = 30;
    const EMPTY_PRODUCTION = '-/-/-/-';
    const MISSING_PEDIGREE_REGISTER = '';
    const EMPTY_DATE_OF_BIRTH = '-';
    const GENERAL_NULL_FILLER = '-';

    const LITTER_SIZE = 'litterSize';
    const LITTER_GROUP = 'litterGroup';
    const N_LING = 'nLing';

    const FAT_DECIMAL_ACCURACY = 2;
    const MUSCLE_THICKNESS_DECIMAL_ACCURACY = 2;
    const GROWTH_DECIMAL_ACCURACY = 1;
    const LAMB_MEAT_INDEX_DECIMAL_ACCURACY = 0;

    const STARS_NULL_VALUE = null;
    const EMPTY_BREED_VALUE = '-/-';
    const EMPTY_INDEX_VALUE = '-/-';
    const EMPTY_SCRAPIE_GENOTYPE = '-/-';

    const GENERATION_OF_ASCENDANTS = 3;

    /** @var array */
    private $data;

    /** @var  ObjectManager */
    private $em;

    /** @var LitterRepository */
    private $litterRepository;

    /** @var MuscleThicknessRepository */
    private $muscleThicknessRepository;

    /** @var BodyFatRepository */
    private $bodyFatRepository;

    /** @var TailLengthRepository */
    private $tailLengthRepository;

    /** @var ExteriorRepository */
    private $exteriorRepository;

    /** @var BreedValuesSetRepository */
    private $breedValuesSetRepository;

    /** @var int */
    private $breedValuesYear;

    /** @var GeneticBase */
    private $geneticBases;

    /** @var array */
    private $lambMeatIndexCoefficients;

    /**
     * PedigreeCertificate constructor.
     * @param ObjectManager $em
     * @param Client $client
     * @param Location $location
     * @param Animal $animal
     * @param int $breedValuesYear
     * @param GeneticBase $geneticBases
     * @param array $lambMeatIndexCoefficients
     */
    public function __construct(ObjectManager $em, Client $client, Location $location, Animal $animal, $breedValuesYear, $geneticBases, $lambMeatIndexCoefficients)
    {
        $this->em = $em;

        $this->litterRepository = $em->getRepository(Litter::class);
        $this->exteriorRepository = $em->getRepository(Exterior::class);
//        $this->muscleThicknessRepository = $em->getRepository(MuscleThickness::class);
//        $this->bodyFatRepository = $em->getRepository(BodyFat::class);
//        $this->tailLengthRepository = $em->getRepository(TailLength::class);
        $this->breedValuesSetRepository = $em->getRepository(BreedValuesSet::class);
        $this->breedValuesYear = $breedValuesYear;
        $this->geneticBases = $geneticBases;
        $this->lambMeatIndexCoefficients = $lambMeatIndexCoefficients;

        $this->data = array();

//        $this->data[ReportLabel::OWNER] = $client;

        $companyName = $this->getCompanyName($location, $client);
        $trimmedClientName = StringUtil::trimStringWithAddedEllipsis($companyName, self::MAX_LENGTH_FULL_NAME);
        $this->data[ReportLabel::OWNER_NAME] = $trimmedClientName;
        $this->data[ReportLabel::ADDRESS] = $location->getCompany()->getAddress();
        $postalCode = $location->getCompany()->getAddress()->getPostalCode();
        if($postalCode != null && $postalCode != '' && $postalCode != ' ') {
            $postalCode = substr($postalCode, 0 ,4).' '.substr($postalCode, 4);
        } else {
            $postalCode = '-';
        }
        $this->data[ReportLabel::POSTAL_CODE] = $postalCode;
        $this->data[ReportLabel::UBN] = $location->getUbn();

        //TODO Phase 2: Add breeder information
        $this->data[ReportLabel::BREEDER] = null; //TODO pass Breeder entity

        //TODO: BreederName
        $breederFirstName = '';
        $breederLastName = '-';
        $trimmedBreederName = StringUtil::getTrimmedFullNameWithAddedEllipsis($breederFirstName, $breederLastName, self::MAX_LENGTH_FULL_NAME);
        $this->data[ReportLabel::BREEDER_NAME] = $trimmedBreederName;
        $this->data[ReportLabel::PEDIGREE_REGISTER_NAME] = $this->getPedigreeRegisterText($animal);

        $emptyAddress = new LocationAddress(); //For now an empty Address entity is passed
        $emptyAddress->setStreetName('-');
        $emptyAddress->setAddressNumber('-');
        $emptyAddress->setAddressNumberSuffix('-');
        $emptyAddress->setCity('-');
        $emptyAddress->setPostalCode('-');
        $postalCode = '-'; //TODO enter a real postalCode here later
        if($postalCode != null && $postalCode != '' && $postalCode != ' ') {
            $postalCode = substr($postalCode, 0 ,4).' '.substr($postalCode, 4);
        } else {
            $postalCode = '-';
        }
        $this->data[ReportLabel::ADDRESS_BREEDER] = $emptyAddress; //TODO pass real Address entity
        $this->data[ReportLabel::POSTAL_CODE_BREEDER] = $postalCode; //TODO pass real Address entity //TODO Add a space between number and last two letters in postalCode
        $this->data[ReportLabel::BREEDER_NUMBER] = '-'; //TODO pass real breeder number

        $keyAnimal = ReportLabel::CHILD_KEY;
//        $this->data[ReportLabel::ANIMALS][$keyAnimal][ReportLabel::ENTITY] = $animal;

        $generation = 0;
        $this->addParents($animal, $keyAnimal, $generation);
        $this->addAnimalValuesToArray($keyAnimal, $animal, $generation);
    }

    /**
     * Recursively add the previous generations of ascendants.
     *
     * @param Animal $animal
     * @param string $keyAnimal
     * @param int $generation
     */
    private function addParents(Animal $animal = null, $keyAnimal, $generation)
    {
        if($generation < self::GENERATION_OF_ASCENDANTS) {

            if($animal != null) {
                $father = $animal->getParentFather();
                $mother = $animal->getParentMother();
            } else {
                $father = null;
                $mother = null;
            }

            if($father == null) { $father = new Ram(); }
            if($mother == null) { $mother = new Ewe(); }

            $keyFather = self::getFatherKey($keyAnimal);
            $keyMother = self::getMotherKey($keyAnimal);

//            $this->data[ReportLabel::ANIMALS][$keyFather][ReportLabel::ENTITY] = $father;
//            $this->data[ReportLabel::ANIMALS][$keyMother][ReportLabel::ENTITY] = $mother;

            $this->addAnimalValuesToArray($keyFather, $father, $generation);
            $this->addAnimalValuesToArray($keyMother, $mother, $generation);

            $generation++;

            //Recursive loop for both parents AFTER increasing the generationCount
            $this->addParents($father, $keyFather, $generation);
            $this->addParents($mother, $keyMother, $generation);
        }
    }

    /**
     * @param string $keyAnimal
     * @return string
     */
    public static function getFatherKey($keyAnimal)
    {
        if($keyAnimal == ReportLabel::CHILD_KEY) {
            $keyFather = ReportLabel::FATHER_KEY;
        } else {
            $keyFather = $keyAnimal . ReportLabel::FATHER_KEY;
        }

        return $keyFather;
    }

    /**
     * @param string $keyAnimal
     * @return string
     */
    public static function getMotherKey($keyAnimal)
    {
        if($keyAnimal == ReportLabel::CHILD_KEY) {
            $keyMother = ReportLabel::MOTHER_KEY;
        } else {
            $keyMother = $keyAnimal . ReportLabel::MOTHER_KEY;
        }

        return $keyMother;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @param string $key
     * @param int $animalId
     * @param int $generation
     */
    private function getAnimalValuesBySql($key, $animalId, $generation)
    {
        $exteriorReplacementString = null;
        $latestExteriorArray = $this->exteriorRepository->getLatestExteriorBySql($animalId, $exteriorReplacementString);

        if($generation < self::GENERATION_OF_ASCENDANTS - 1) {
            //Only retrieve the breedValues and lambMeatIndices for the child, parents and grandparents.

            //Breedvalues: The actual breed value not the measurements!
            $breedValues = self::getUnformattedBreedValues($this->em, $animalId, $this->breedValuesYear, $this->geneticBases);
            $formattedBreedValues = BreedValueUtil::getFormattedBreedValues($breedValues);

            // Set values in result array
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCLE_THICKNESS] = $formattedBreedValues[BreedValueLabel::MUSCLE_THICKNESS];
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BODY_FAT] = $formattedBreedValues[BreedValueLabel::FAT];
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GROWTH] = $formattedBreedValues[BreedValueLabel::GROWTH];
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TAIL_LENGTH] = Utils::fillNullOrEmptyString(null);

            //LambMeatIndex with Accuracy
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::VL] = self::getFormattedLambMeatIndexWithAccuracy($breedValues);
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SL] = Utils::fillZero(0.00);

            if($key == ReportLabel::CHILD_KEY) {
                $this->addBreedIndex($breedValues[BreedValueLabel::LAMB_MEAT_INDEX]);
            }
        }

        //Litter in which animal was born
        $litterData = $this->litterRepository->getLitterData($animalId);
        $litterSize = self::GENERAL_NULL_FILLER;
        $nLing = self::GENERAL_NULL_FILLER;
        $litterGroup = self::GENERAL_NULL_FILLER;
        if($litterData != null) {
            $litterSize = $litterData[JsonInputConstant::SIZE];
            $nLing = $litterData[JsonInputConstant::N_LING];
            $litterGroup = $litterData[JsonInputConstant::LITTER_GROUP];
        }

        $sql = "SELECT a.id, CONCAT(a.uln_country_code, a.uln_number) as uln, CONCAT(a.pedigree_country_code, a.pedigree_number) as stn,
                  scrapie_genotype, breed, breed_type, breed_code, date_of_birth, gender
                FROM animal a WHERE a.id = ".$animalId;
        $animalData = $this->em->getConnection()->query($sql)->fetch();
        
        //AnimalData
        $uln = $animalData[JsonInputConstant::ULN];
        $stn = $animalData[JsonInputConstant::STN];
        $scrapieGenotype = $animalData[JsonInputConstant::SCRAPIE_GENOTYPE];
        $breed = $animalData[JsonInputConstant::BREED];
        $breedCode = $animalData[JsonInputConstant::BREED_CODE];
        $breedType = $animalData[JsonInputConstant::BREED_TYPE];
        $scrapieGenotype = $animalData[JsonInputConstant::SCRAPIE_GENOTYPE];
        $gender = $animalData[JsonInputConstant::GENDER];

        $dateOfBirthString = self::EMPTY_DATE_OF_BIRTH;
        $dateOfBirthDateTime = null;
        if($animalData[JsonInputConstant::DATE_OF_BIRTH] != null) {
            $dateOfBirthDateTime = new \DateTime($animalData[JsonInputConstant::DATE_OF_BIRTH]);
            $dateOfBirthString = $dateOfBirthDateTime->format('d-m-Y');
        }

        $inspectionDateString = null;
        $inspectionDateDateTime = null;
        if($latestExteriorArray[JsonInputConstant::MEASUREMENT_DATE] != null && $latestExteriorArray[JsonInputConstant::MEASUREMENT_DATE] != $exteriorReplacementString) {
            $inspectionDateDateTime = new \DateTime($latestExteriorArray[JsonInputConstant::MEASUREMENT_DATE]);
            $inspectionDateString = $inspectionDateDateTime->format('d-m-Y');
        }

        //Litters of offspring, data for production string
        $offspringLitterData = $this->litterRepository->getAggregatedLitterDataOfOffspring($animalId); //data from the litter table

        $litterCount = $offspringLitterData[JsonInputConstant::LITTER_COUNT];
        $totalStillbornCount = $offspringLitterData[JsonInputConstant::TOTAL_STILLBORN_COUNT];
        $totalBornAliveCount = $offspringLitterData[JsonInputConstant::TOTAL_BORN_ALIVE_COUNT];
        $totalOffSpringCountByLitterData = $totalBornAliveCount + $totalStillbornCount;
        $earliestLitterDate = $offspringLitterData[JsonInputConstant::EARLIEST_LITTER_DATE];
        $latestLitterDate = $offspringLitterData[JsonInputConstant::LATEST_LITTER_DATE];

        
        /* Set values into array */
        //Note the BreedValues and LambMeatIndex values are already set above

        //Exterior
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = Utils::fillZero($latestExteriorArray[JsonInputConstant::SKULL]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = Utils::fillZero($latestExteriorArray[JsonInputConstant::PROGRESS]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCULARITY] = Utils::fillZero($latestExteriorArray[JsonInputConstant::MUSCULARITY]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PROPORTION] = Utils::fillZero($latestExteriorArray[JsonInputConstant::PROPORTION]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TYPE] = Utils::fillZero($latestExteriorArray[JsonInputConstant::EXTERIOR_TYPE]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LEGWORK] = Utils::fillZero($latestExteriorArray[JsonInputConstant::LEG_WORK]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FUR] = Utils::fillZero($latestExteriorArray[JsonInputConstant::FUR]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENERAL_APPEARANCE] = Utils::fillZero($latestExteriorArray[JsonInputConstant::GENERAL_APPEARANCE]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::HEIGHT] = Utils::fillZero($latestExteriorArray[JsonInputConstant::HEIGHT]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TORSO_LENGTH] = Utils::fillZero($latestExteriorArray[JsonInputConstant::TORSO_LENGTH]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREAST_DEPTH] = Utils::fillZero($latestExteriorArray[JsonInputConstant::BREAST_DEPTH]);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MARKINGS] = Utils::fillZero($latestExteriorArray[JsonInputConstant::MARKINGS]);

        //Litter
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $litterSize;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_GROUP] = $litterGroup;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $nLing;

        //Offspring
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount);

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = Utils::fillNullOrEmptyString($uln);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = Utils::fillNullOrEmptyString($stn);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($scrapieGenotype, self::EMPTY_SCRAPIE_GENOTYPE);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = Utils::fillNullOrEmptyString($breed);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = Utils::fillNullOrEmptyString(Translation::translateBreedType($breedType));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = Utils::fillNullOrEmptyString($breedCode);
        /* Dates. The null checks for dates are done here including the formatting */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = Utils::fillNullOrEmptyString($dateOfBirthString, self::EMPTY_DATE_OF_BIRTH);
        //NOTE measurementDate and inspectionDate are identical!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = self::getTypeAndInspectionDateByDateTime(
            $latestExteriorArray[JsonInputConstant::KIND], $inspectionDateDateTime, self::GENERAL_NULL_FILLER
        );

        /* variables translated to Dutch */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENDER] = Translation::getGenderInDutchByAnimal($gender);

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = self::parseProductionString($dateOfBirthDateTime, $earliestLitterDate, $latestLitterDate, $litterCount, $totalOffSpringCountByLitterData, $totalBornAliveCount, $gender);

        //TODO NOTE the name column contains VSM primaryKey at the moment Utils::fillNullOrEmptyString($animal->getName());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::NAME] = self::GENERAL_NULL_FILLER;

        //TODO Add these variables to the entities INCLUDING NULL CHECKS!!!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BLINDNESS_FACTOR] = self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PREDICATE] = self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NAME] = self::GENERAL_NULL_FILLER;
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NUMBER] = self::GENERAL_NULL_FILLER;
    }


    /**
     * @param string $key
     * @param Animal|Ewe|Ram $animal
     * @param int $generation
     */
    private function addAnimalValuesToArray($key, $animal, $generation)
    {
        //Body Measurement Values
//        $latestMuscleThickness = $this->muscleThicknessRepository->getLatestMuscleThickness($animal);
//        $latestBodyFatAsString = $this->bodyFatRepository->getLatestBodyFatAsString($animal);
//        $latestTailLength = $this->tailLengthRepository->getLatestTailLength($animal);
        $latestExterior = $this->exteriorRepository->getLatestExterior($animal);

        //TODO IF PedigreeCertificate is fixed.
        //Only retrieve the breedValues and lambMeatIndices for the child, parents and grandparents.
        if($generation < self::GENERATION_OF_ASCENDANTS - 1) {
            //Breedvalues: The actual breed value not the measurements!
            $breedValues = self::getUnformattedBreedValues($this->em, $animal->getId(), $this->breedValuesYear, $this->geneticBases);
            $formattedBreedValues = BreedValueUtil::getFormattedBreedValues($breedValues);

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCLE_THICKNESS] = $formattedBreedValues[BreedValueLabel::MUSCLE_THICKNESS];
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BODY_FAT] = $formattedBreedValues[BreedValueLabel::FAT];
            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GROWTH] = $formattedBreedValues[BreedValueLabel::GROWTH];

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TAIL_LENGTH] = Utils::fillNullOrEmptyString(null);

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::VL] = self::getFormattedLambMeatIndexWithAccuracy($breedValues);

            $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SL] = Utils::fillZero(0.00); //TODO Add sl variable to Exterior Entity ??? Or is this just Tail Length?

            if($key == ReportLabel::CHILD_KEY) {
                $this->addBreedIndex($breedValues[BreedValueLabel::LAMB_MEAT_INDEX]);
            }
        }

        //Exterior
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = Utils::fillZero($latestExterior->getSkull());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = Utils::fillZero($latestExterior->getProgress());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCULARITY] = Utils::fillZero($latestExterior->getMuscularity());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PROPORTION] = Utils::fillZero($latestExterior->getProportion());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TYPE] = Utils::fillZero($latestExterior->getExteriorType());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LEGWORK] = Utils::fillZero($latestExterior->getLegWork());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::FUR] = Utils::fillZero($latestExterior->getFur());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENERAL_APPEARANCE] = Utils::fillZero($latestExterior->getGeneralAppearence());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::HEIGHT] = Utils::fillZero($latestExterior->getHeight());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TORSO_LENGTH] = Utils::fillZero($latestExterior->getTorsoLength());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREAST_DEPTH] = Utils::fillZero($latestExterior->getBreastDepth());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MARKINGS] = Utils::fillZero($latestExterior->getMarkings());

        //Litter
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $this->getLitterValues($animal)->get(self::LITTER_SIZE);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_GROUP] = $this->getLitterValues($animal)->get(self::LITTER_GROUP);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $this->getLitterValues($animal)->get(self::N_LING);

        //Offspring
        $litterCount = $this->litterRepository->getLitters($animal)->count();
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount);

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = Utils::fillNullOrEmptyString($animal->getUlnCountryCode().$animal->getUlnNumber());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode().$animal->getPedigreeNumber());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::NAME] = self::GENERAL_NULL_FILLER;// TODO NOTE the name column contains VSM primaryKey at the moment Utils::fillNullOrEmptyString($animal->getName());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), self::EMPTY_SCRAPIE_GENOTYPE);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = Utils::fillNullOrEmptyString($animal->getBreed());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = Utils::fillNullOrEmptyString(Translation::translateBreedType($animal->getBreedType()));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = Utils::fillNullOrEmptyString($animal->getBreedCode());
        /* Dates. The null checks for dates are done here including the formatting */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = NullChecker::getNullCheckedDateOfBirthAsString($animal, self::EMPTY_DATE_OF_BIRTH);
        //NOTE measurementDate and inspectionDate are identical!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = $this->getTypeAndInspectionDate($latestExterior);

        /* variables translated to Dutch */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENDER] = Translation::getGenderInDutchByAnimal($animal);

        //TODO Add these variables to the entities INCLUDING NULL CHECKS!!!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BLINDNESS_FACTOR] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PREDICATE] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = $this->parseProductionStringByAnimal($this->em, $animal);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NAME] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NUMBER] = '-';
    }


    /**
     * @param float $lambMeatIndex
     */
    private function addBreedIndex($lambMeatIndex)
    {
        //Empty
        $breederStarCount = 0;
        $motherBreederStarCount = 0;
        $fatherBreederStarCount = 0;
        $exteriorStarCount = 0;
        
        $lambMeatStarCount = StarValueUtil::getStarValue($lambMeatIndex);

        $this->data[ReportLabel::BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($breederStarCount);
        $this->data[ReportLabel::M_BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($motherBreederStarCount);
        $this->data[ReportLabel::F_BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($fatherBreederStarCount);
        $this->data[ReportLabel::EXT_INDEX_STARS] = TwigOutputUtil::createStarsIndex($exteriorStarCount);
        $this->data[ReportLabel::VL_INDEX_STARS] = TwigOutputUtil::createStarsIndex($lambMeatStarCount);

        $this->data[ReportLabel::BREEDER_INDEX_NO_ACC] = 'ab/acc';
        $this->data[ReportLabel::M_BREEDER_INDEX_NO_ACC] = 'mb/acc';
        $this->data[ReportLabel::F_BREEDER_INDEX_NO_ACC] = 'fb/acc';
        $this->data[ReportLabel::EXT_INDEX_NO_ACC] = 'ex/acc';
    }


    /**
     * @param Exterior $exterior
     * @param string $replacementString
     * @return string
     */
    private function getTypeAndInspectionDate(Exterior $exterior, $replacementString = self::GENERAL_NULL_FILLER)
    {
        $measurementDate = $exterior->getMeasurementDate();
        $kind = $exterior->getKind();
        return self::getTypeAndInspectionDateByDateTime($kind, $measurementDate, $replacementString);
    }


    /**
     * @param string $kind
     * @param \DateTime $measurementDate
     * @param string $replacementString
     * @return string
     */
    private static function getTypeAndInspectionDateByDateTime($kind, $measurementDate, $replacementString = self::GENERAL_NULL_FILLER)
    {
        $kindExists = NullChecker::isNotNull($kind) && $kind != self::GENERAL_NULL_FILLER;
        $measurementDateExists = NullChecker::isNotNull($measurementDate);

        if($kindExists && $measurementDateExists) {
            return $kind.' '.$measurementDate->format('d-m-Y');

        } elseif (!$kindExists && $measurementDateExists) {
            return $measurementDate->format('d-m-Y');

        } else {
            return $replacementString;
        }

    }


    /**
     * @param Animal $animal
     * @return ArrayCollection
     */
    private function getLitterValues(Animal $animal)
    {
        $litterValues = new ArrayCollection();

        //Litter animal was born in
        if($animal->getLitter() != null) {
            $litter = $animal->getLitter();
            if($litter->getSize() != null) {
                $litterSize = $litter->getSize();
                $nLing = $litter->getSize().'-ling';
            } else {
                $litterSize = self::GENERAL_NULL_FILLER;
                $nLing = self::GENERAL_NULL_FILLER;
            }

            //TODO/WARNING litterGroup in Litter refers to the MixBlup Identification != worpgroep!!!
            if(true) { //FIXME WITH REAL DATA
                $litterGroup = self::GENERAL_NULL_FILLER;
            } else {
                $litterGroup = self::GENERAL_NULL_FILLER;
            }

        } else {
            $litterSize = self::GENERAL_NULL_FILLER;
            $litterGroup = self::GENERAL_NULL_FILLER;
            $nLing = self::GENERAL_NULL_FILLER;
        }

        $litterValues->set(self::LITTER_SIZE, $litterSize);
        $litterValues->set(self::LITTER_GROUP, $litterGroup);
        $litterValues->set(self::N_LING, $nLing);

        return $litterValues;
    }

    /**
     * @param Ewe|Ram $parent
     * @return int
     */
    private function getOffspringCount($parent)
    {
        if($parent instanceof Ram || $parent instanceof Ewe) {
            if($parent->getChildren() != null) {
                return $parent->getChildren()->count();
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }


    /**
     *
     * nLing = stillBornCount + bornAliveCount
     * production = (ewe) litters (litter * nLing)

    production: a/b/c/d e
    a: age in years from birth until date of last Litter
    b: litterCount
    c: total number of offspring (stillborn + bornAlive)
    d: total number of bornAliveCount
    e: (*) als een ooi ooit heeft gelammerd tussen een leeftijd van 6 en 18 maanden
     *
     * @param ObjectManager $em
     * @param Animal $animal
     * @return string
     */
    public static function parseProductionStringByAnimal(ObjectManager $em, $animal)
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);

        if($animal instanceof Ewe || $animal instanceof Ram) {
            /** @var Ewe $animal */
            $litters = $animal->getLitters();
            $litterCount = $litters->count();

            if($litterCount > 0) {
                $stillbornCount = 0;
                $bornAliveCount = 0;
                $earliestLitterDate = $litters->first()->getLitterDate();
                $latestLitterDate = $litters->last()->getLitterDate();
                $dateOfBirth = $animal->getDateOfBirth();

                foreach ($litters as $litter) {
                    /** @var Litter $litter */
                    $stillbornCount += $litter->getStillbornCount();
                    $bornAliveCount += $litter->getBornAliveCount();
                    $litterDate = $litter->getLitterDate();
                    if($litterDate < $earliestLitterDate) {
                        $earliestLitterDate = $litterDate;
                    }
                }
                $totalBornCount = $stillbornCount + $bornAliveCount;

                //By default there is no oneYearMark
                $oneYearMark = '';
                if($animal instanceof Ewe) {
                    if(TimeUtil::isGaveBirthAsOneYearOld($dateOfBirth, $earliestLitterDate)){
                        $oneYearMark = '*';
                    }
                }

                $ageInTheNsfoSystem = TimeUtil::ageInSystemForProductionValue($dateOfBirth, $latestLitterDate);
                if($ageInTheNsfoSystem == null) {
                    $ageInTheNsfoSystem = '-';
                }

                return $ageInTheNsfoSystem.'/'.$litterCount.'/'.$totalBornCount.'/'.$bornAliveCount.$oneYearMark;


            } else {
                //If Ewe or Ram has no litters in Database
                return self::EMPTY_PRODUCTION;
            }
        } else {
            //Animal is a Neuter
            return self::EMPTY_PRODUCTION;
        }
    }


    /**
     * @param \DateTime $dateOfBirth
     * @param \DateTime $earliestLitterDate
     * @param \DateTime $latestLitterDate
     * @param int $litterCount
     * @param int $totalBornCount
     * @param int $bornAliveCount
     * @param string $gender
     * @return string
     */
    public static function parseProductionString($dateOfBirth, $earliestLitterDate, $latestLitterDate, $litterCount, $totalBornCount, $bornAliveCount, $gender)
    {
        if($gender == GenderType::NEUTER || $gender == GenderType::O || $litterCount == 0) { return self::EMPTY_PRODUCTION; }

        //By default there is no oneYearMark
        $oneYearMark = '';
        if($gender == GenderType::FEMALE || $gender == GenderType::V) {
            if(TimeUtil::isGaveBirthAsOneYearOld($dateOfBirth, $earliestLitterDate)){
                $oneYearMark = '*';
            }
        }

        $ageInTheNsfoSystem = TimeUtil::ageInSystemForProductionValue($dateOfBirth, $latestLitterDate);
        if($ageInTheNsfoSystem == null) {
            $ageInTheNsfoSystem = '-';
        }

        return $ageInTheNsfoSystem.'/'.$litterCount.'/'.$totalBornCount.'/'.$bornAliveCount.$oneYearMark;
    }


    /**
     * @param Location $location
     * @param Client $client
     * @return string
     */
    private function getCompanyName($location, $client)
    {
        $company = $location->getCompany();
        if($company != null) {
            return $company->getCompanyName();
        } else {
            $company = $client->getCompanies()->first();
            if($company != null) {
                return $company->getCompanyName();
            } else {
                return '-';
            }
        }
    }


    /**
     * @param Animal $animal
     * @return string
     */
    private function getPedigreeRegisterText($animal)
    {
        $registerName = $animal->getPedigreeRegisterFullName();

        if($registerName != null && $registerName != '') {
            return 'Namens: '.$registerName;
        } else {
            return self::MISSING_PEDIGREE_REGISTER;
        }
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
    private static function getFormattedLambMeatIndexWithAccuracy($breedValuesArray)
    {
        return BreedValueUtil::getFormattedLamMeatIndexWithAccuracy(
            $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX],
            $breedValuesArray[BreedValueLabel::LAMB_MEAT_INDEX_ACCURACY],
            self::EMPTY_INDEX_VALUE);
    }



}