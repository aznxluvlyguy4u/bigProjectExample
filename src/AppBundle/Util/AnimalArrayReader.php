<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\GenderType;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

class AnimalArrayReader
{
    /**
     * @param array $animalArray
     * @return array
     */
    public static function readUlnOrPedigree($animalArray)
    {
        $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            return [
                Constant::TYPE_NAMESPACE => Constant::ULN_NAMESPACE,
                JsonInputConstant::ULN_COUNTRY_CODE => $ulnCountryCode,
                JsonInputConstant::ULN_NUMBER => $ulnNumber,
                JsonInputConstant::DATA => $ulnCountryCode.$ulnNumber,
                JsonInputConstant::TRANSLATION_KEY => Constant::ULN_NAMESPACE,
            ];
        }


        $pedigreeCountryCode = ArrayUtil::get(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = ArrayUtil::get(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {

            return [
                Constant::TYPE_NAMESPACE => Constant::PEDIGREE_NAMESPACE,
                JsonInputConstant::PEDIGREE_COUNTRY_CODE => $pedigreeCountryCode,
                JsonInputConstant::PEDIGREE_NUMBER => $pedigreeNumber,
                JsonInputConstant::DATA => $pedigreeCountryCode.$pedigreeNumber,
                JsonInputConstant::TRANSLATION_KEY => Constant::STN_NAMESPACE,
            ];
        }

        return [
            Constant::TYPE_NAMESPACE => null,
            JsonInputConstant::ULN_COUNTRY_CODE => null,
            JsonInputConstant::ULN_NUMBER => null,
            JsonInputConstant::DATA => null,
            JsonInputConstant::TRANSLATION_KEY => null,
        ];
    }


    /**
     * @param $animalArray
     * @param string $separator
     * @return null|string
     */
    public static function getIdString($animalArray, $separator = '')
    {
        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            return $ulnCountryCode.$separator.$ulnNumber;
        }
        
        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {
            return $pedigreeCountryCode.$separator.$pedigreeNumber;
        }

        return null;
    }


    /**
     * @param array $animalArray
     * @param string $separator
     * @return array
     */
    public static function getUlnAndPedigreeInArray($animalArray, $separator = '')
    {
        $animal = array();

        $ulnCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray);
        $ulnNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::ULN_NUMBER, $animalArray);
        if ($ulnCountryCode != null && $ulnNumber != null) {
            $animal[ReportLabel::ULN] = $ulnCountryCode.$separator.$ulnNumber;
        }

        $pedigreeCountryCode = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_COUNTRY_CODE, $animalArray);
        $pedigreeNumber = Utils::getNullCheckedArrayValue(JsonInputConstant::PEDIGREE_NUMBER, $animalArray);
        if ($pedigreeCountryCode != null && $pedigreeNumber != null) {
            $animal[ReportLabel::PEDIGREE] = $pedigreeCountryCode.$separator.$pedigreeNumber;
        }

        return $animal;
    }

    
    /**
     * @param array $animals
     * @return null|Animal|Ram|Ewe|Neuter
     */
    public static function prioritizeImportedAnimalFromArray($animals)
    {
        $count = count($animals);
        if($count == 1) {
            return ArrayUtil::firstValue($animals);

        } elseif($count > 1) {

            $lowestAnimalId = self::getLowestAnimalId($animals);

            /** @var Animal $animal */
            foreach ($animals as $animal) {
                //Prioritize imported animal, based on vsmId saved in name column
                if($animal->getName() != null) {
                    return $animal;
                }
            }

            /** @var Animal $animal */
            foreach ($animals as $animal) {
                //Then prioritize non-Neuter animal
                if($animal->getGender() != GenderType::NEUTER && $animal->getGender() != GenderType::O) {
                    return $animal;
                }
            }

            /** @var Animal $animal */
            foreach ($animals as $animal) {
                //Then prioritize Animal with lowest id
                if($animal->getId() == $lowestAnimalId) {
                    return $animal;
                }
            }

            //By default return the first Animal
            return ArrayUtil::firstValue($animals);

        } else { //count == 0
            return null;
        }
    }


    /**
     * @param $animals
     * @return int|null
     */
    public static function getLowestAnimalId($animals)
    {
        if(count($animals) == 0) { return null; }
        /** @var Animal $firstAnimal */
        $firstAnimal = ArrayUtil::firstValue($animals);
        $lowestId = $firstAnimal->getId();

        /** @var Animal $animal */
        foreach ($animals as $animal) {
            if($animal->getId() < $lowestId) { $lowestId = $animal->getId(); }
        }
        return $lowestId;
    }


    /**
     * @param $array
     * @param null $nullReplacement
     * @return null|string
     */
    public static function getUlnFromArray($array, $nullReplacement = null)
    {
        $ulnCountryCode = ArrayUtil::get(JsonInputConstant::ULN_COUNTRY_CODE, $array);
        $ulnNumber = ArrayUtil::get(JsonInputConstant::ULN_NUMBER,$array);
        
        if($ulnCountryCode != null && $ulnNumber != null) {
            return $ulnCountryCode.$ulnNumber;
        }
        return $nullReplacement;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Collection $content
     * @return array|int[]
     */
    public static function getAnimalsInContentArray(EntityManagerInterface $em, Collection $content): array
    {
        $animalIds = [];

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);

        foreach ($content->getKeys() as $key) {
            if ($key == Constant::ANIMALS_NAMESPACE) {
                $animalArrays = $content->get($key);

                foreach ($animalArrays as $animalArray) {
                    $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
                    $ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                    $animalId = $animalRepository->sqlQueryAnimalIdByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);

                    $animalIds[] = $animalId;
                }
            }
        }

        return $animalIds;
    }


    public static function getFirstUlnFromAnimalsArray(Collection $content): ?string {
        foreach ($content->getKeys() as $key) {
            if ($key == Constant::ANIMALS_NAMESPACE) {
                $animalArrays = $content->get($key);

                if (count($animalArrays) != 1) {
                    break;
                }

                foreach ($animalArrays as $animalArray) {
                    $ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
                    $ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                    return $ulnCountryCode.$ulnNumber;
                }
            }
        }
        return null;
    }
}