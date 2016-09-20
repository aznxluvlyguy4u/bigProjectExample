<?php

namespace AppBundle\Migration;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedCode;
use AppBundle\Entity\BreedCodes;
use AppBundle\Enumerator\BreedCodeType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class BreedCodeReformatter
{
    const BREED_CODE_VALUE_PARTS = 8; //If this is not 8, refactor reformatBreedCodeValues function
    const PERSIST_BATCH_SIZE = 1000;

    /** @var ObjectManager $em */
    private $em;

    /** @var ArrayCollection */
    private $animals;

    public function __construct(ObjectManager $em, $isMigrateBreedCodes = true, ArrayCollection $animals = null)
    {
        $this->em = $em;

        if($animals != null) {
            $this->animals = $animals;
        }

        if($isMigrateBreedCodes) {
            $this->migrate();
        }
    }

    /**
     * @param ArrayCollection $animals
     */
    public function setAnimals($animals)
    {
        $this->animals = $animals;
    }

    /**
     * @return  ArrayCollection $animals
     */
    public function getAnimals()
    {
        return $this->animals;
    }


    private function getAllAnimalsIfNull()
    {
        if($this->animals == null) {
            $this->animals = $this->em->getRepository(Animal::class)->findAll();
        }
        return $this->animals;
    }


    public function migrate($isRegenerateAlsoExistingBreedCodes = false)
    {
        $animals = $this->getAllAnimalsIfNull();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $count = 0;

        /** @var Animal $animal */
        foreach ($this->animals as $animal) {

            $breedCode = $animal->getBreedCode();

            if(strlen($breedCode) >= 4) { //Verify if there actually is a breedCode
                $breedCodesSet = $animal->getBreedCodes();
                if($breedCodesSet == null) {
                    $breedCodesSet = new BreedCodes();
                    $breedCodesSet->setAnimal($animal);
                    $animal->setBreedCodes($breedCodesSet);
                    $this->em->persist($breedCodesSet);
                    $this->em->persist($animal);
                }
                
                $reformattedBreedCode = self::reformatBreedCode($breedCode);
                $breedCodeNames = $reformattedBreedCode->getKeys();

                $oldCodes = $breedCodesSet->getCodes();
                $isGenerateNewCodes = false;

                if($isRegenerateAlsoExistingBreedCodes) {

                    if($oldCodes->count() > 0) { $breedCodesSet->removeAllCodes(); }
                    $isGenerateNewCodes = true;
                    
                } else {
                    if($oldCodes->count() == 0) {
                        $isGenerateNewCodes = true;
                    }
                }

                if($isGenerateNewCodes) {
                    foreach ($breedCodeNames as $breedCodeName) {
                        $newBreedCode = new BreedCode($breedCodesSet, $breedCodeName, $reformattedBreedCode->get($breedCodeName));
                        $breedCodesSet->addCode($newBreedCode);
                        $newBreedCode->setCodeSet($breedCodesSet);
                        $this->em->persist($newBreedCode);
                    }
                    $this->em->persist($breedCodesSet);
                    $this->em->persist($animal);
                }
            }

            $count++;
            if($count%self::PERSIST_BATCH_SIZE == 0) {
                $this->flushPlus();
            }
        }
        $this->flushPlus();
    }

    /**
     * @param array $breedCode
     * @return ArrayCollection
     */
    public static function reformatBreedCode($breedCode)
    {
        $breedCode = Utils::separateLettersAndNumbersOfString($breedCode);

        //Initialize default values
        $results = new ArrayCollection();

        for($i = 0; $i < sizeof($breedCode); $i+=2) {
            $results->set($breedCode[$i], //breedCodeType like TE, CF, NH etc.
                      self::reformatBreedCodeValues($breedCode[$i+1])
            );
        }

        return $results;
    }

    /**
     * BreedCode grouping for TestAttributes ('toetskenmerken'): TE, CF, NH, OV(other)
     *
     * @param string $breedCodeType
     * @return string
     */
    public static function getMixBlupTestAttributesBreedCodeType($breedCodeType)
    {
        switch ($breedCodeType) {
            //TE
            case BreedCodeType::TE:
                return BreedCodeType::TE;
            case BreedCodeType::BT:
                return BreedCodeType::TE;
            case BreedCodeType::DK:
                return BreedCodeType::TE;

            //non-TE breedCodeTypes
            case BreedCodeType::CF:
                return BreedCodeType::CF;
            case BreedCodeType::NH:
                return BreedCodeType::NH;
            case BreedCodeType::SW:
                return BreedCodeType::SW;

            //Everything not of one of the above types
            default:
                return BreedCodeType::OV;
        }
    }

    /**
     * BreedCode grouping for ExteriorAttributes: TE, SW, BM, OV(other)
     *
     * @param string $breedCodeType
     * @return string
     */
    public static function getMixBlupExteriorAttributesBreedCodeType($breedCodeType)
    {
        switch ($breedCodeType) {
            //TE
            case BreedCodeType::TE:
                return BreedCodeType::TE;
            case BreedCodeType::BT:
                return BreedCodeType::TE;
            case BreedCodeType::DK:
                return BreedCodeType::TE;

            //non-TE breedCodeTypes
            case BreedCodeType::SW:
                return BreedCodeType::SW;
            case BreedCodeType::BM:
                return BreedCodeType::BM;

            //Everything not of one of the above types
            default:
                return BreedCodeType::OV;
        }
    }

    /**
     * BreedCode grouping for Fertility: TE, CF, SW, NH, OV(other)
     *
     * @param string $breedCodeType
     * @return string
     */
    public static function getMixBlupFertilityBreedCodeType($breedCodeType)
    {
        switch ($breedCodeType) {
            //TE
            case BreedCodeType::TE:
                return BreedCodeType::TE;
            case BreedCodeType::BT:
                return BreedCodeType::TE;
            case BreedCodeType::DK:
                return BreedCodeType::TE;

            //non-TE breedCodeTypes
            case BreedCodeType::CF:
                return BreedCodeType::CF;
            case BreedCodeType::SW:
                return BreedCodeType::SW;
            case BreedCodeType::NH:
                return BreedCodeType::NH;
            case BreedCodeType::GP:
                return BreedCodeType::GP;
            case BreedCodeType::BM:
                return BreedCodeType::BM;

            //Everything not of one of the above types
            default:
                return BreedCodeType::OV;
        }
    }

    /**
     * @param int $breedCodeValue
     * @return int
     */
    private static function reformatBreedCodeValues($breedCodeValue)
    {
        switch ($breedCodeValue) {
            case 0:
                return 0;
            case 12:
                return 1;
            case 25:
                return 2;
            case 38:
                return 3;
            case 50:
                return 4;
            case 62:
                return 5;
            case 75:
                return 6;
            case 88:
                return 7;
            case 100:
                return 8;
            default:
                return null;
        }

    }

    private function flushPlus()
    {
        $this->em->flush();
        $this->em->clear();
        gc_collect_cycles();
    }
}