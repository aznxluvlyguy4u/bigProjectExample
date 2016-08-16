<?php

namespace AppBundle\Migration;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\MixBlupBreedCode;
use AppBundle\Enumerator\BreedCodeType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

class BreedCodeReformatter
{
    const BREED_CODE_VALUE_PARTS = 8; //If this is not 8, refactor reformatBreedCodeValues function

    /** @var EntityManager $em */
    private $em;

    /** @var ArrayCollection */
    private $animals;

    public function __construct(EntityManager $em, $isMigrateBreedCodes = true, ArrayCollection $animals = null)
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

    
    private function getAllAnimalsIfNull()
    {
        if($this->animals == null) {
            $this->animals = $this->em->getRepository(Animal::class)->findAll();
        }
        return $this->animals;
    }

    
    public function migrate()
    {
        $animals = $this->getAllAnimalsIfNull();

        $count = 0;
        $persistBatchSize = 100;

        /** @var Animal $animal */
        foreach ($animals as $animal) {

            $reformattedBreedCode = self::reformatBreedCode($animal->getBreedCode());
            $te = $reformattedBreedCode->get(BreedCodeType::TE);
            $cf = $reformattedBreedCode->get(BreedCodeType::CF);
            $nh = $reformattedBreedCode->get(BreedCodeType::NH);
            $ov = $reformattedBreedCode->get(BreedCodeType::OV);

            $mixBlupBreedCode = $animal->getMixBlupBreedCode();
            if($mixBlupBreedCode == null) {
                $mixBlupBreedCode = new MixBlupBreedCode($te, $cf, $nh, $ov);
                $mixBlupBreedCode->setAnimal($animal);
                $animal->setMixBlupBreedCode($mixBlupBreedCode);
            } else {
                $mixBlupBreedCode->setTE($te);
                $mixBlupBreedCode->setCF($cf);
                $mixBlupBreedCode->setNH($nh);
                $mixBlupBreedCode->setOV($ov);
            }
            $this->em->persist($mixBlupBreedCode);

            $count++;
            if($count%$persistBatchSize) {
                $this->em->flush();
            }
        }
        $this->em->flush();
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
        $results->set(BreedCodeType::TE,0);
        $results->set(BreedCodeType::CF,0);
        $results->set(BreedCodeType::NH,0);
        $results->set(BreedCodeType::OV,0);

        for($i = 0; $i < sizeof($breedCode); $i+=2) {
            $breedCodeType = $breedCode[$i];
            $reformattedBreedCodeTypeValue = self::reformatBreedCodeValues($breedCode[$i+1]);

            $mixBlupBreedCodeType = self::getMixBlupBreedCodeType($breedCodeType);
            $newBreedCodeTypeValue = $results->get($mixBlupBreedCodeType) + $reformattedBreedCodeTypeValue;

            $results->set($mixBlupBreedCodeType, $newBreedCodeTypeValue);
        }

        //verification check
        $sumOfBreedCodeValues = $results->get(BreedCodeType::TE) + $results->get(BreedCodeType::CF) +
                                $results->get(BreedCodeType::NH) + $results->get(BreedCodeType::OV);
        if($sumOfBreedCodeValues != self::BREED_CODE_VALUE_PARTS) {
            $results->set(Constant::IS_VALID_NAMESPACE, false);
        } else {
            $results->set(Constant::IS_VALID_NAMESPACE, true);
        }

        return $results;
    }

    /**
     * @param string $breedCodeType
     * @return string
     */
    private static function getMixBlupBreedCodeType($breedCodeType)
    {
        switch ($breedCodeType) {
            case BreedCodeType::TE:
                return BreedCodeType::TE;
            case BreedCodeType::BT:
                return BreedCodeType::TE;
            case BreedCodeType::DK:
                return BreedCodeType::TE;
            case BreedCodeType::CF:
                return BreedCodeType::CF;
            case BreedCodeType::NH:
                return BreedCodeType::NH;
            default: //Everything not of one of the above types
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
}