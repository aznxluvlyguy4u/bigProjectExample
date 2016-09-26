<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientOffspring
{
    const CHILD_ID = -1;
    const GENERATION_OF_ASCENDANTS = 3;
    const GENERATION_DIRECT_PARENTS = 1;
    const NO_INBREEDING = 0;

    /** @var ObjectManager */
    private $em;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var array */
    private $parentSearchArray;

    /** @var array */
    private $childrenSearchArray;

    /** @var array */
    private $closedLoopPaths;

    /** @var array */
    private $commonAncestors;

    /** @var float */
    private $inbreedingCoefficient;

    /** @var int */
    private $fatherId;

    /** @var int */
    private $motherId;

    /**
     * InbreedingCoefficientOffspring constructor.
     * @param ObjectManager $em
     * @param int $fatherId
     * @param int $motherId
     * @param array $parentSearchArray
     * @param array $childrenSearchArray
     */
    public function __construct(ObjectManager $em, $fatherId, $motherId, $parentSearchArray = array(), $childrenSearchArray = array())
    {
        $this->em = $em;
        $this->animalRepository = $em->getRepository(Animal::class);
        $this->fatherId = $fatherId;
        $this->motherId = $motherId;

        $this->closedLoopPaths = array();
        $this->commonAncestors = array();
        $this->parentSearchArray = $parentSearchArray;
        $this->childrenSearchArray = $childrenSearchArray;

        $this->inbreedingCoefficient = $this->calculateInbreedingCoefficient();
    }


    /**
     * @return float
     */
    public function getValue()
    {
        return $this->inbreedingCoefficient;
    }


    /**
     * @return float
     */
    private function calculateInbreedingCoefficient()
    {
        if($this->fatherId == null || $this->motherId == null) {
            return self::NO_INBREEDING;
        }

        // 1. Set parent and child searchArray values for hypothetical child
        $this->setSearchArrayValuesOfChild();

        // 2. Traverse parents and create search arrays
        $this->addParents($this->fatherId, self::GENERATION_DIRECT_PARENTS);
        $this->addParents($this->motherId, self::GENERATION_DIRECT_PARENTS);
dump($this->childrenSearchArray, $this->parentSearchArray);die;
        // 3. Find closed loop paths and
        // 4. Recursively calculate the inbreeding coefficients of the common ancestors
        $this->getClosedLoopPaths();

        // 5. Calculate inbreeding coefficient

        return 777.55;
    }


    private function setSearchArrayValuesOfChild()
    {
        $this->initializeChildrenSearchArrayKey($this->fatherId);
        $this->initializeChildrenSearchArrayKey($this->motherId);
        $this->childrenSearchArray[$this->fatherId][self::CHILD_ID] = self::CHILD_ID;
        $this->childrenSearchArray[$this->motherId][self::CHILD_ID] = self::CHILD_ID;

        $this->initializeParentSearchArrayKey(self::CHILD_ID);
        $this->parentSearchArray[self::CHILD_ID][$this->fatherId] = $this->fatherId;
        $this->parentSearchArray[self::CHILD_ID][$this->motherId] = $this->motherId;
    }


    /**
     * Recursively add the previous generations of ascendants.
     *
     * @param int $animalId
     * @param int $generation
     */
    private function addParents($animalId = null, $generation)
    {
        if($generation < self::GENERATION_OF_ASCENDANTS && $animalId != null) {

            $motherId = $this->animalRepository->getMotherId($animalId);
            $fatherId = $this->animalRepository->getFatherId($animalId);

            $this->addToSearchArrays($animalId, $motherId);
            $this->addToSearchArrays($animalId, $fatherId);

            $generation++;

            //Recursive loop for both parents AFTER increasing the generationCount
            $this->addParents($motherId, $generation);
            $this->addParents($fatherId, $generation);
        }
    }


    /**
     * @param int $childId
     * @param int $parentId
     */
    private function addToSearchArrays($childId, $parentId)
    {
        //Set parent and children
        if($parentId != null) {
            $this->initializeParentSearchArrayKey($childId);
            $this->parentSearchArray[$childId][$parentId] = $parentId;
            $this->initializeChildrenSearchArrayKey($parentId);
            $this->childrenSearchArray[$parentId][$childId] = $childId;
        }
    }


    /**
     * @param int $animalId
     */
    private function initializeParentSearchArrayKey($animalId)
    {
        //Initialize array
        if(!array_key_exists($animalId, $this->parentSearchArray)) {
            $this->parentSearchArray[$animalId] = array();
        }
    }


    /**
     * @param int $animalId
     */
    private function initializeChildrenSearchArrayKey($animalId)
    {
        if(!array_key_exists($animalId, $this->childrenSearchArray)) {
            $this->childrenSearchArray[$animalId] = array();
        }
    }


    private function getClosedLoopPaths()
    {
        $animalIds = array_keys($this->childrenSearchArray);

        foreach ($animalIds as $animalId)
        {
            $childrenArray = $this->childrenSearchArray[$animalId];
            if(count($childrenArray) > 1) {
                $this->getClosedLoopPathsOfAnimal($animalId);
                //Calculate the inbreeding coefficients of all common ancestors
                $commonAncestorInbreedingCoefficientResult = new InbreedingCoefficient($this->em, $animalId, $this->parentSearchArray, $this->childrenSearchArray);
                $this->commonAncestors[$animalId] = $commonAncestorInbreedingCoefficientResult->getValue();
            }
        }
    }


    /**
     * @param $animalId
     */
    private function getClosedLoopPathsOfAnimal($animalId)
    {
        //traverse all possible paths
        //TODO
        //If child is in both left and right branches -> add to closed loop path
    }
}


/**
 * Class InbreedingCoefficient
 * @package AppBundle\Util
 */
class InbreedingCoefficient
{

    /** @var float */
    private $inbreedingCoefficientOffspring;
    
    /**
     * InbreedingCoefficient constructor.
     * @param ObjectManager $em
     * @param int $animalId
     */
    public function __construct(ObjectManager $em, $animalId, $parentSearchArray = array(), $childrenSearchArray = array())
    {
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $em->getRepository(Animal::class);
        $fatherId = $animalRepository->getFatherId($animalId);
        $motherId = $animalRepository->getMotherId($animalId);

        //If searchArray is passed, then only return parentIds if the searchArray contains them.
        if(array_key_exists($animalId, $parentSearchArray)) {
            $parentArray = $parentSearchArray[$animalId];
            $fatherId = $this->getParentIdInParentArray($fatherId, $parentArray);
            $motherId = $this->getParentIdInParentArray($motherId, $parentArray);
        }

        $this->inbreedingCoefficientOffspring = new InbreedingCoefficientOffspring($em, $fatherId, $motherId, $parentSearchArray, $childrenSearchArray);
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->inbreedingCoefficientOffspring->getValue();
    }


    /**
     * @param int $parentId
     * @param array $parentArray
     * @return int|null
     */
    private function getParentIdInParentArray($parentId, $parentArray)
    {
        if(!is_int($parentId)) {
            return null;

        } elseif(array_key_exists($parentId, $parentArray)) {
           return $parentId;

        } else {
            return null;
        }
    }
}