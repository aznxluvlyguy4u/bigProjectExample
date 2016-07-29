<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BodyFat;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationAddress;
use AppBundle\Entity\MuscleThickness;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use AppBundle\Util\Translation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Component\CssSelector\XPath\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

class PedigreeCertificate
{
    const LITTER_SIZE = 'litterSize';
    const LITTER_GROUP = 'litterGroup';
    const N_LING = 'nLing';

    /** @var array */
    private $data;

    /** @var int */
    private $generationOfAscendants;

    /** @var  EntityManager */
    private $em;

    /**
     * PedigreeCertificate constructor.
     * @param EntityManager $em
     * @param Client $client
     * @param Location $location
     * @param Animal $animal
     * @param int $generationOfAscendants
     */
    public function __construct(EntityManager $em, Client $client, Location $location, Animal $animal, $generationOfAscendants = 3)
    {
        $this->em = $em;

        $this->data = array();
        $this->generationOfAscendants = $generationOfAscendants;

        $this->data[ReportLabel::OWNER] = $client;
        $this->data[ReportLabel::OWNER_NAME] = $client->getFirstName() . " " . $client->getLastName();
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
        $this->data[ReportLabel::BREEDER_NAME] = '-'; //TODO
        $this->data[ReportLabel::BREEDER_NAME_CROPPED] = '-'; //TODO incase the format has no space, use this cropped name
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
        $latestMuscleThickness = $this->em->getRepository(MuscleThickness::class)->getLatestMuscleThickness($animal);
        $latestBodyFat = $this->em->getRepository(BodyFat::class)->getLatestBodyFat($animal);
        $latestTailLength = $this->em->getRepository(TailLength::class)->getLatestTailLength($animal);
        $latestExterior = $this->em->getRepository(Exterior::class)->getLatestExterior($animal);
        
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MUSCLE_THICKNESS] =Utils::fillZero( $latestMuscleThickness);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BODY_FAT] = Utils::fillZero($latestBodyFat);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::TAIL_LENGTH] = Utils::fillZero($latestTailLength);
        
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SKULL] = Utils::fillZero($latestExterior->getSkull());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DEVELOPMENT] = Utils::fillZero(0.00); //TODO Add development variable to Exterior Entity
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
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GROWTH] = Utils::fillZero(0.00); //TODO Add growth variable to Exterior Entity ???
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::VL] = Utils::fillZero(0.00); //TODO Add Vl variable to Exterior Entity ???
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SL] = Utils::fillZero(0.00); //TODO Add sl variable to Exterior Entity ??? Or is this just Tail Length?

        //Litter
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_SIZE] = $this->getLitterValues($animal)->get(self::LITTER_SIZE);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_GROUP] = $this->getLitterValues($animal)->get(self::LITTER_GROUP);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::N_LING] = $this->getLitterValues($animal)->get(self::N_LING);
        
        //Offspring
        $litterCount = $this->em->getRepository(Litter::class)->getLitters($animal)->count();
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::LITTER_COUNT] = Utils::fillZero($litterCount);
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::OFFSPRING_COUNT] =  Utils::fillZero($this->getOffspringCount($animal));

        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::ULN] = Utils::fillNullOrEmptyString($animal->getUlnCountryCode().$animal->getUlnNumber());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PEDIGREE] = Utils::fillNullOrEmptyString($animal->getPedigreeCountryCode().$animal->getPedigreeNumber());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::NAME] = Utils::fillNullOrEmptyString($animal->getName());
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::SCRAPIE] = Utils::fillNullOrEmptyString($animal->getScrapieGenotype(), '-/-');
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED] = Utils::fillNullOrEmptyString(Translation::translateBreedType($animal->getBreed()));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_TYPE] = Utils::fillNullOrEmptyString(Translation::translateBreedType($animal->getBreedType()));
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREED_CODE] = Utils::fillNullOrEmptyString($animal->getBreedCode());

        /* Dates. The null checks for dates are in the twig file, because it has to be combined with the formatting */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::DATE_OF_BIRTH] = $animal->getDateOfBirth();
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::INSPECTION_DATE] = null; //TODO
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::MEASUREMENT_DATE] = $latestExterior; //TODO

        /* variables translated to Dutch */
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::GENDER] = Translation::getGenderInDutch($animal);

        //TODO Add these variables to the entities INCLUDING NULL CHECKS!!!
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BLINDNESS_FACTOR] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PREDICATE] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::PRODUCTION] = '-/-/-/-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NAME] = '-';
        $this->data[ReportLabel::ANIMALS][$key][ReportLabel::BREEDER_NUMBER] = '-';

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
            return $parent->getChildren()->count();
        } else {
            return 0;
        }
    }
}