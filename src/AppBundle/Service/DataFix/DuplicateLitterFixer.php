<?php

namespace AppBundle\Service\DataFix;


use AppBundle\Entity\Litter;
use AppBundle\Entity\LitterRepository;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class DuplicateLitterFixer
 */
class DuplicateLitterFixer extends DuplicateFixerBase
{

    /** @var LitterRepository $litterRepository */
    private $litterRepository;


    /**
     * DuplicateAnimalsFixer constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
        $this->litterRepository = $this->em->getRepository(Litter::class);
    }


    /**
     * @param $primaryLitterId
     * @param $secondaryLitterId
     * @return bool
     */
    public function mergeByPairsById($primaryLitterId, $secondaryLitterId)
    {
        if (!is_int($primaryLitterId) || !is_int($secondaryLitterId) || intval($primaryLitterId) == intval($secondaryLitterId)) {
            return false;
        }

        $primaryLitter = $this->litterRepository->find($primaryLitterId);
        $secondaryLitter = $this->litterRepository->find($secondaryLitterId);

        if ($primaryLitter === null || $secondaryLitter === null) { return false; }

        $primaryLitterId = $primaryLitter->getId();
        $secondaryLitterId = $secondaryLitter->getId();

        if ($primaryLitterId === null || $secondaryLitterId === null) { return false; }

        /* 2. merge values */
        $isLitterIdMergeSuccessFul = $this->mergeLitterIdValuesInTables($primaryLitterId, $secondaryLitterId);
        $isLitterValueMergeSuccessFul = $this->mergeEmptyLitterValues($primaryLitter, $secondaryLitter);

        /* 3 Remove unnecessary duplicate */
        if($isLitterIdMergeSuccessFul && $isLitterValueMergeSuccessFul) {
            $this->em->remove($secondaryLitter);
            $this->em->flush();
            return true;
        }
        return false;
    }


    /***
     * @param $primaryLitterId
     * @param $secondaryLitterId
     * @return bool
     */
    private function mergeLitterIdValuesInTables($primaryLitterId, $secondaryLitterId)
    {
        if((!is_int($primaryLitterId) && !ctype_digit($primaryLitterId)) ||
            (!is_int($secondaryLitterId) && !ctype_digit($secondaryLitterId))) { return false; }

        $tableNamesByVariableType = [
            [ self::TABLE_NAME => 'animal',         self::VARIABLE_TYPE => 'litter_id' ],
            [ self::TABLE_NAME => 'stillborn',      self::VARIABLE_TYPE => 'litter_id' ],
            [ self::TABLE_NAME => 'declare_birth',  self::VARIABLE_TYPE => 'litter_id' ],
        ];

        $mergeResults = $this->mergeColumnValuesInTables($primaryLitterId, $secondaryLitterId, $tableNamesByVariableType);
        return $mergeResults[self::IS_MERGE_SUCCESSFUL];
    }


    /**
     * @param Litter $primaryLitter
     * @param Litter $secondaryLitter
     * @return bool
     */
    private function mergeEmptyLitterValues(Litter $primaryLitter, Litter $secondaryLitter)
    {
        $areAnyValuesUpdated = false;

        if ($primaryLitter->getActionBy() === null && $secondaryLitter->getActionBy() !== null) {
           $primaryLitter->setActionBy($secondaryLitter->getActionBy()); $areAnyValuesUpdated = true;
        }

        //Do not merge a secondaryLitter Revoke status nor revoke values

        if ($primaryLitter->getRelationNumberKeeper() === null && $secondaryLitter->getRelationNumberKeeper() !== null) {
            $primaryLitter->setRelationNumberKeeper($secondaryLitter->getRelationNumberKeeper()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getUbn() === null && $secondaryLitter->getUbn() !== null) {
            $primaryLitter->setUbn($secondaryLitter->getUbn()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getAnimalFather() === null && $secondaryLitter->getAnimalFather() !== null) {
            $primaryLitter->setAnimalFather($secondaryLitter->getAnimalFather()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getAnimalMother() === null && $secondaryLitter->getAnimalMother() !== null) {
            $primaryLitter->setAnimalMother($secondaryLitter->getAnimalMother()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getMate() === null && $secondaryLitter->getMate() !== null) {
            $primaryLitter->setMate($secondaryLitter->getMate()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getLitterOrdinal() === null && $secondaryLitter->getLitterOrdinal() !== null) {
            $primaryLitter->setLitterOrdinal($secondaryLitter->getLitterOrdinal()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getSuckleCount() === null && $secondaryLitter->getSuckleCount() !== null) {
            $primaryLitter->setSuckleCount($secondaryLitter->getSuckleCount()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getSuckleCountUpdateDate() === null && $secondaryLitter->getSuckleCountUpdateDate() !== null) {
            $primaryLitter->setSuckleCountUpdateDate($secondaryLitter->getSuckleCountUpdateDate()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getGestationPeriod() === null && $secondaryLitter->getGestationPeriod() !== null) {
            $primaryLitter->setGestationPeriod($secondaryLitter->getGestationPeriod()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getBirthInterval() === null && $secondaryLitter->getBirthInterval() !== null) {
            $primaryLitter->setBirthInterval($secondaryLitter->getBirthInterval()); $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getGestationPeriod() === null && $secondaryLitter->getGestationPeriod() !== null) {
            $primaryLitter->setGestationPeriod($secondaryLitter->getGestationPeriod()); $areAnyValuesUpdated = true;
        }


        $isGeneDiversityUpdated = false;
        if ($primaryLitter->getHeterosis() === null && $secondaryLitter->getHeterosis() !== null) {
            $primaryLitter->setHeterosis($secondaryLitter->getHeterosis());
            $isGeneDiversityUpdated = true; $areAnyValuesUpdated = true;
        }

        if ($primaryLitter->getRecombination() === null && $secondaryLitter->getRecombination() !== null) {
            $primaryLitter->setRecombination($secondaryLitter->getRecombination());
            $isGeneDiversityUpdated = true; $areAnyValuesUpdated = true;
        }

        if ($isGeneDiversityUpdated) {
            if ($primaryLitter->isUpdatedGeneDiversity()) {
                //Mark updatedGeneDiversity = false, so the geneDiversity values can be double checked more easily.
                $primaryLitter->setUpdatedGeneDiversity(false);
                $areAnyValuesUpdated = true;
            }
        }


        if ($areAnyValuesUpdated) {
            $this->em->persist($primaryLitter);
            //Flush only at the end of the successful merge.
        }


        return true;
    }
}