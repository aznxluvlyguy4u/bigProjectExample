<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Util\AnimalArrayReader;

/**
 * Class RamRepository
 * @package AppBundle\Entity
 */
class RamRepository extends AnimalRepository {

    /**
     * @param $animalArray
     * @return null|Ram
     */
    public function getRamByArray($animalArray)
    {
        $animalData = AnimalArrayReader::readUlnOrPedigree($animalArray);
        if($animalData[Constant::TYPE_NAMESPACE] == Constant::ULN_NAMESPACE) {
            return $this->findOneBy(
                ['ulnCountryCode' => $animalData[JsonInputConstant::ULN_COUNTRY_CODE],
                    'ulnNumber' => $animalData[JsonInputConstant::ULN_NUMBER]
                ]
            );

        } elseif ($animalData[Constant::TYPE_NAMESPACE] == Constant::PEDIGREE_NAMESPACE) {
            return $this->findOneBy(
                ['pedigreeCountryCode' => $animalData[JsonInputConstant::PEDIGREE_COUNTRY_CODE],
                    'pedigreeNumber' => $animalData[JsonInputConstant::PEDIGREE_NUMBER]
                ]
            );

        } else {
            return null;
        }
    }

}