<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\BodyFatRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\ExteriorRepository;
use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\MuscleThicknessRepository;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\TailLengthRepository;
use AppBundle\Util\NullChecker;
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

    const LITTER_SIZE = 'litterSize';
    const LITTER_GROUP = 'litterGroup';
    const N_LING = 'nLing';

    /** @var array */
    private $data;

    /** @var int */
    private $generationOfAscendants;

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

    /**
     * PedigreeCertificate constructor.
     * @param ObjectManager $em
     * @param Client $client
     * @param Location $location
     * @param Animal $animal
     * @param int $generationOfAscendants
     */
    public function __construct(ObjectManager $em, Client $client, Location $location, Animal $animal, $generationOfAscendants = 3)
    {
        $this->em = $em;

        $this->litterRepository = $em->getRepository(Litter::class);
        $this->exteriorRepository = $em->getRepository(Exterior::class);
//        $this->muscleThicknessRepository = $em->getRepository(MuscleThickness::class);
//        $this->bodyFatRepository = $em->getRepository(BodyFat::class);
//        $this->tailLengthRepository = $em->getRepository(TailLength::class);

        $this->data = array();
        $this->generationOfAscendants = $generationOfAscendants;

        $this->data[ReportLabel::OWNER] = $client;

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

        //TODO Phase 2: BreedIndices (now mock values are used)
        $this->addBreedIndex($animal);

        //TODO Phase 2: Add breeder information
        $this->data[ReportLabel::BREEDER] = null; //TODO pass Breeder entity

        //TODO: BreederName
        $breederFirstName = '';
        $breederLastName = '-';
        $trimmedBreederName = StringUtil::getTrimmedFullNameWithAddedEllipsis($breederFirstName, $breederLastName, self::MAX_LENGTH_FULL_NAME);
        $this->data[ReportLabel::BREEDER_NAME] = $trimmedBreederName;

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
        $this->data[ReportLabel::ANIMALS][$keyAnimal][ReportLabel::ENTITY] = $animal;

        $generation = 0;
        $this->addParents($animal, $keyAnimal, $generation);
        $this->addAnimalValuesToArray($keyAnimal, $animal);
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
        if($generation < $this->generationOfAscendants) {

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

            $this->data[ReportLabel::ANIMALS][$keyFather][ReportLabel::ENTITY] = $father;
            $this->data[ReportLabel::ANIMALS][$keyMother][ReportLabel::ENTITY] = $mother;
            
            $this->addAnimalValuesToArray($keyFather, $father);
            $this->addAnimalValuesToArray($keyMother, $mother);

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
     * @param Animal|Ewe|Ram $animal
     */
    private function addAnimalValuesToArray($key, $animal)
    {
        //Body Measurement Values
//        $latestMuscleThickness = $this->muscleThicknessRepository->getLatestMuscleThickness($animal);
//        $latestBodyFatAsString = $this->bodyFatRepository->getLatestBodyFatAsString($animal);
//        $latestTailLength = $this->tailLengthRepository->getLatestTailLength($animal);
        $latestExterior = $this->exteriorRepository->getLatestExterior($animal);

        //Breedvalues: The actual breed value not the measurements!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCLE_THICKNESS] = Utils::fillNullOrEmptyString(null);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BODY_FAT] = Utils::fillNullOrEmptyString(null);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TAIL_LENGTH] = Utils::fillNullOrEmptyString(null);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GROWTH] = Utils::fillZero(0.00);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::VL] = Utils::fillZero(0.00); //TODO Add Vl variable to Exterior Entity ???
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SL] = Utils::fillZero(0.00); //TODO Add sl variable to Exterior Entity ??? Or is this just Tail Length?

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
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::OFFSPRING_COUNT] =  Utils::fillZero($this->getOffspringCount($animal));

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = Utils::fillNullOrEmptyString($animal->getUlnCountryCode().$animal->getUlnNumber());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode().$animal->getPedigreeNumber());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::NAME] = '-';// TODO NOTE the name column contains VSM primaryKey at the moment Utils::fillNullOrEmptyString($animal->getName());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), '-/-');
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = Utils::fillNullOrEmptyString($animal->getBreed());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = Utils::fillNullOrEmptyString(Translation::translateBreedType($animal->getBreedType()));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = Utils::fillNullOrEmptyString($animal->getBreedCode());

        /* Dates. The null checks for dates are in the twig file, because it has to be combined with the formatting */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = $animal->getDateOfBirth();
        //NOTE measurementDate and inspectionDate are identical!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = $this->getTypeAndInspectionDate($latestExterior);

        /* variables translated to Dutch */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENDER] = Translation::getGenderInDutch($animal);

        //TODO Add these variables to the entities INCLUDING NULL CHECKS!!!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BLINDNESS_FACTOR] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PREDICATE] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = $this->parseProductionString($animal);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NAME] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NUMBER] = '-';

    }


    /**
     * @param Animal $animal
     */
    private function addBreedIndex(Animal $animal)
    {
        //TODO Phase 2: BreedIndices
        $breederStarCount = 5;
        $motherBreederStarCount = 4;
        $fatherBreederStarCount = 3;
        $exteriorStarCount = 2;
        $meatLambStarCount = 1;

        $this->data[ReportLabel::BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($breederStarCount);
        $this->data[ReportLabel::M_BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($motherBreederStarCount);
        $this->data[ReportLabel::F_BREEDER_INDEX_STARS] = TwigOutputUtil::createStarsIndex($fatherBreederStarCount);
        $this->data[ReportLabel::EXT_INDEX_STARS] = TwigOutputUtil::createStarsIndex($exteriorStarCount);
        $this->data[ReportLabel::VL_INDEX_STARS] = TwigOutputUtil::createStarsIndex($meatLambStarCount);

        $this->data[ReportLabel::BREEDER_INDEX_NO_ACC] = 'ab/acc';
        $this->data[ReportLabel::M_BREEDER_INDEX_NO_ACC] = 'mb/acc';
        $this->data[ReportLabel::F_BREEDER_INDEX_NO_ACC] = 'fb/acc';
        $this->data[ReportLabel::EXT_INDEX_NO_ACC] = 'ex/acc';
        $this->data[ReportLabel::VL_INDEX_NO_ACC] = 'vl/acc';
    }


    /**
     * @param Exterior $exterior
     * @return string
     */
    private function getTypeAndInspectionDate($exterior)
    {
        $measurementDate = $exterior->getMeasurementDate();
        $kind = $exterior->getKind();

        $kindExists = NullChecker::isNotNull($kind);
        $measurementDateExists = NullChecker::isNotNull($measurementDate);

        if($kindExists && $measurementDateExists) {
            return $kind.' '.$measurementDate->format('d-m-Y');

        } elseif (!$kindExists && $measurementDateExists) {
            return $measurementDate->format('d-m-Y');

        } else {
            return '-';
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
                $litterSize = '-';
                $nLing = '-';
            }

            if($litter->getLitterGroup() != null) {
                $litterGroup = $litter->getLitterGroup();
            } else {
                $litterGroup = '-';
            }

        } else {
            $litterSize = '-';
            $litterGroup = '-';
            $nLing = '-';
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
    a: age in years from birth until slaughter or until animal is not in nsfo leeftijd in jaren van geboorte tot slacht of totdat het niet in het nsfo systeem zit
    b: litterCount
    c: total number of offspring (stillborn + bornAlive)
    d: total number of bornAliveCount
    e: (*) als een ooi ooit heeft gelammerd tussen een leeftijd van 6 en 18 maanden
     *
     * @param Animal $animal
     * @return string
     */
    public static function parseProductionString($animal)
    {
        if($animal instanceof Ewe) {
            /** @var Ewe $animal */
            $litters = $animal->getLitters();
            $litterCount = $litters->count();

            if($litterCount > 0) {
                $stillbornCount = 0;
                $bornAliveCount = 0;
                $earliestLitterDate = $litters->first()->getLitterDate();

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

                if(TimeUtil::isGaveBirthAsOneYearOld($animal->getDateOfBirth(), $earliestLitterDate)){
                    $oneYearMark = '*';
                } else {
                    $oneYearMark = '';
                }

                $ageInTheNsfoSystem = TimeUtil::ageInSystem($animal);
                if($ageInTheNsfoSystem == null) {
                    $ageInTheNsfoSystem = '-';
                }

                return $ageInTheNsfoSystem.'/'.$litterCount.'/'.$totalBornCount.'/'.$bornAliveCount.$oneYearMark;


            } else {
                return self::EMPTY_PRODUCTION;
            }
        } else {
            return self::EMPTY_PRODUCTION;
        }
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
    
}