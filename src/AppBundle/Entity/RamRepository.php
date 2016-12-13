<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\AnimalArrayReader;
use Doctrine\Common\Collections\Criteria;

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
            return $this->findRamByUlnCountryCodeAndNumber($animalData[JsonInputConstant::ULN_COUNTRY_CODE], $animalData[JsonInputConstant::ULN_NUMBER]);

        } elseif ($animalData[Constant::TYPE_NAMESPACE] == Constant::PEDIGREE_NAMESPACE) {
            return $this->findRamByPedigreeCountryCodeAndNumber($animalData[JsonInputConstant::PEDIGREE_COUNTRY_CODE], $animalData[JsonInputConstant::PEDIGREE_NUMBER]);

        } else {
            return null;
        }
    }


    /**
     * @param string $ulnString
     * @return Ram|null
     */
    public function findRamByUlnString($ulnString)
    {
        $uln = Utils::getUlnFromString($ulnString);
        return $this->findRamByUlnCountryCodeAndNumber($uln[Constant::ULN_COUNTRY_CODE_NAMESPACE], $uln[Constant::ULN_NUMBER_NAMESPACE]);
    }


    /**
     * @param $countryCode
     * @param $ulnNumber
     * @return null|Ram
     */
    public function findRamByUlnCountryCodeAndNumber($countryCode, $ulnNumber)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ulnCountryCode', $countryCode))
            ->andWhere(Criteria::expr()->eq('ulnNumber', $ulnNumber))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('gender', GenderType::MALE),
                Criteria::expr()->eq('gender', GenderType::M)
            ))
            ->orderBy(['id' => Criteria::ASC])
        ;

        $animals = $this->getManager()->getRepository(Ram::class)
            ->matching($criteria);

        return AnimalArrayReader::prioritizeImportedAnimalFromArray($animals);
    }


    /**
     * @param $countryCode
     * @param $pedigreeNumber
     * @return null|Ram
     */
    public function findRamByPedigreeCountryCodeAndNumber($countryCode, $pedigreeNumber)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('pedigreeCountryCode', $countryCode))
            ->andWhere(Criteria::expr()->eq('pedigreeNumber', $pedigreeNumber))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('gender', GenderType::MALE),
                Criteria::expr()->eq('gender', GenderType::M)
            ))
            ->orderBy(['id' => Criteria::ASC])
        ;

        $animals = $this->getManager()->getRepository(Ram::class)
            ->matching($criteria);

        return AnimalArrayReader::prioritizeImportedAnimalFromArray($animals);
    }
}