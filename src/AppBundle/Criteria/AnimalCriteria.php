<?php


namespace AppBundle\Criteria;


use AppBundle\Constant\Constant;
use AppBundle\Util\ArrayUtil;
use Doctrine\Common\Collections\Criteria;

class AnimalCriteria
{
    /**
     * @param array $ids
     * @param string $animalKey
     * @return Criteria
     */
    public static function byIds(array $ids = [], $animalKey = 'animal')
    {
        return Criteria::create()->where(Criteria::expr()->in($animalKey.'.id', $ids));
    }


    /**
     * @param array $ulnPartsArray
     * @param array $stnPartsArray
     * @param string $animalKey
     * @return Criteria
     * @throws \Exception
     */
    public static function byUlnOrStnParts(array $ulnPartsArray = [], array $stnPartsArray = [], $animalKey = 'animal')
    {
        $criteria = Criteria::create();

        foreach ($ulnPartsArray as $ulnParts) {
            $ulnCountryCode = ArrayUtil::get(Constant::ULN_COUNTRY_CODE_NAMESPACE, $ulnParts, null);
            $ulnNumber = ArrayUtil::get(Constant::ULN_NUMBER_NAMESPACE, $ulnParts, null);

            if ($ulnCountryCode === null) {
                throw new \Exception(Constant::ULN_COUNTRY_CODE_NAMESPACE, ' key is missing from array');
            }
            if ($ulnNumber === null) {
                throw new \Exception(Constant::ULN_NUMBER_NAMESPACE, ' key is missing from array');
            }

            $criteria->orWhere(
                Criteria::expr()->andX(
                    Criteria::expr()->eq($animalKey.'.ulnCountryCode', $ulnCountryCode),
                    Criteria::expr()->eq($animalKey.'.ulnNumber', $ulnNumber)
                )
            );
        }


        foreach ($stnPartsArray as $stnParts) {
            $pedigreeCountryCode = ArrayUtil::get(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $stnParts, null);
            $pedigreeNumber = ArrayUtil::get(Constant::PEDIGREE_NUMBER_NAMESPACE, $stnParts, null);

            if ($pedigreeCountryCode === null) {
                throw new \Exception(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, ' key is missing from array');
            }
            if ($pedigreeNumber === null) {
                throw new \Exception(Constant::PEDIGREE_NUMBER_NAMESPACE, ' key is missing from array');
            }

            $criteria->orWhere(
                Criteria::expr()->andX(
                    Criteria::expr()->eq($animalKey . '.pedigreeCountryCode', $pedigreeCountryCode),
                    Criteria::expr()->eq($animalKey . '.pedigreeNumber', $pedigreeNumber)
                )
            );
        }

        return $criteria;
    }

}