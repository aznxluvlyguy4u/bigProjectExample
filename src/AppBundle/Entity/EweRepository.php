<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\AnimalArrayReader;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Class EweRepository
 * @package AppBundle\Entity
 */
class EweRepository extends AnimalRepository {

    /**
     * @param Ewe $mother
     * @param boolean $isPersist
     * @param boolean $isFlush
     */
    public function generateLitterIds($mother, $isPersist = true, $isFlush = true)
    {
        /** @var ObjectManager $em */
        $em = $this->getManager();

        if($mother instanceof Ewe) {
            $uln = $mother->getUln();

            $litters = $mother->getLitters();
            $litterCount = $litters->count();

            for($i = 0; $i < $litterCount; $i++) {
                /** @var Litter $litter */
                $litter = $litters->get($i);
                $litterNumber = str_pad($i+1, 2, "0", STR_PAD_LEFT);
                $litter->setLitterGroup($uln.'_'.$litterNumber);

                if($isPersist) { $em->persist($litter); }
            }
            if($isPersist) { $em->persist($mother); }
            if($isFlush) { $em->flush(); }
        }
    }


    /**
     * @param int $startId
     * @param int $endId
     * @return Collection
     */
    public function getEwesById($startId, $endId)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->gte('id', $startId))
            ->andWhere(Criteria::expr()->lte('id', $endId))
            ->orderBy(['id' => Criteria::ASC])
        ;

        return $this->getManager()->getRepository(Ewe::class)
            ->matching($criteria);
    }

    /**
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMaxEweId()
    {
        $sql = "SELECT MAX(id) FROM ewe";
        return $this->executeSqlQuery($sql);
    }


    /**
     * @param $animalArray
     * @return null|Ewe
     */
    public function getEweByArray($animalArray)
    {
        $animalData = AnimalArrayReader::readUlnOrPedigree($animalArray);
        if($animalData[Constant::TYPE_NAMESPACE] == Constant::ULN_NAMESPACE) {
            return $this->findEweByUlnCountryCodeAndNumber($animalData[JsonInputConstant::ULN_COUNTRY_CODE], $animalData[JsonInputConstant::ULN_NUMBER]);

        } elseif ($animalData[Constant::TYPE_NAMESPACE] == Constant::PEDIGREE_NAMESPACE) {
            return $this->findEweByPedigreeCountryCodeAndNumber($animalData[JsonInputConstant::PEDIGREE_COUNTRY_CODE], $animalData[JsonInputConstant::PEDIGREE_NUMBER]);
            
        } else {
            return null;
        }
    }


    /**
     * @param $countryCode
     * @param $ulnNumber
     * @return null|Ewe
     */
    public function findEweByUlnCountryCodeAndNumber($countryCode, $ulnNumber)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ulnCountryCode', $countryCode))
            ->andWhere(Criteria::expr()->eq('ulnNumber', $ulnNumber))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('gender', GenderType::FEMALE),
                Criteria::expr()->eq('gender', GenderType::V)
            ))
            ->orderBy(['id' => Criteria::ASC])
        ;

        $animals = $this->getManager()->getRepository(Ewe::class)
            ->matching($criteria);

        return AnimalArrayReader::prioritizeImportedAnimalFromArray($animals);
    }


    /**
     * @param $countryCode
     * @param $pedigreeNumber
     * @return null|Ewe
     */
    public function findEweByPedigreeCountryCodeAndNumber($countryCode, $pedigreeNumber)
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('pedigreeCountryCode', $countryCode))
            ->andWhere(Criteria::expr()->eq('pedigreeNumber', $pedigreeNumber))
            ->andWhere(Criteria::expr()->orX(
                Criteria::expr()->eq('gender', GenderType::FEMALE),
                Criteria::expr()->eq('gender', GenderType::V)
            ))
            ->orderBy(['id' => Criteria::ASC])
        ;

        $animals = $this->getManager()->getRepository(Ewe::class)
            ->matching($criteria);

        return AnimalArrayReader::prioritizeImportedAnimalFromArray($animals);
    }
}