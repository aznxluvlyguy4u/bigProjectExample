<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\PedigreeRegister;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientOffspring
{
    const CHILD_ID = -1;
    const GENERATION_OF_ASCENDANTS = 3;
    const GENERATION_DIRECT_PARENTS = 1;
    const NO_INBREEDING = 0;
    const SEPARATOR = ';';

    /** @var ObjectManager */
    private $em;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var array */
    private $parentSearchArray;

    /** @var array */
    private $childrenSearchArray;

    /** @var array */
    private $originalChildrenSearchArray;

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

    /** @var array */
    private $paths;

    /**
     * InbreedingCoefficientOffspring constructor.
     * @param ObjectManager $em
     * @param int $fatherId
     * @param int $motherId
     * @param array $parentSearchArray
     * @param array $originalChildrenSearchArray
     */
    public function __construct(ObjectManager $em, $fatherId, $motherId, $parentSearchArray = array(), $originalChildrenSearchArray = array())
    {
        $this->em = $em;
        $this->animalRepository = $em->getRepository(Animal::class);
        $this->fatherId = $fatherId;
        $this->motherId = $motherId;

        $this->closedLoopPaths = array();
        $this->commonAncestors = array();
        $this->childrenSearchArray = array();
        $this->parentSearchArray = $parentSearchArray;
        $this->originalChildrenSearchArray = $originalChildrenSearchArray;

        $this->inbreedingCoefficient = $this->run();
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
    private function run()
    {
        if($this->fatherId == null || $this->motherId == null) {
            return self::NO_INBREEDING;
        }

        // 1. Set parent and child searchArray values for hypothetical child
        $this->setSearchArrayValuesOfChild();

        // 2. Traverse parents and create search arrays
        $this->addParents($this->fatherId, self::GENERATION_DIRECT_PARENTS);
        $this->addParents($this->motherId, self::GENERATION_DIRECT_PARENTS);

        // 3. Find closed loop paths and
        // 4. Recursively calculate the inbreeding coefficients of the common ancestors
        $this->getClosedLoopPaths();

        // 5. Calculate inbreeding coefficient
        $this->calculateInbreedingCoefficient();
    }


    private function calculateInbreedingCoefficient()
    {
        $inbreedingCoefficient = 0;
        if(count($this->closedLoopPaths) == 0) {
            $this->inbreedingCoefficient = $inbreedingCoefficient;

        } else {
            $inbreedingCoefficientCommonAncestor = 0; //TODO
            foreach ($this->closedLoopPaths as $closedLoopPath) {
                $animalsInLoop = count($closedLoopPath);
                $inbreedingCoefficient += pow(0.5,$animalsInLoop)*(1+$inbreedingCoefficientCommonAncestor);
            }
        }
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
        // Check if inside recursive loop or not
        if(empty($this->originalChildrenSearchArray)) {

            if($generation < self::GENERATION_OF_ASCENDANTS && $animalId != null) {

                $motherId = $this->animalRepository->getMotherId($animalId);
                $fatherId = $this->animalRepository->getFatherId($animalId);

                $this->addToChildrenSearchArrays($animalId, $motherId);
                $this->addToChildrenSearchArrays($animalId, $fatherId);
                $this->addToParentsSearchArrays($animalId, $motherId);
                $this->addToParentsSearchArrays($animalId, $fatherId);

                $generation++;

                //Recursive loop for both parents AFTER increasing the generationCount
                $this->addParents($motherId, $generation);
                $this->addParents($fatherId, $generation);
            }

        } else {

            if($generation < self::GENERATION_OF_ASCENDANTS && $animalId != null) {

                $motherId = $this->animalRepository->getMotherId($animalId);
                $fatherId = $this->animalRepository->getFatherId($animalId);

                if(array_key_exists($fatherId, $this->originalChildrenSearchArray)){
                    $this->addToChildrenSearchArrays($animalId, $fatherId);
                }

                if(array_key_exists($motherId, $this->originalChildrenSearchArray)){
                    $this->addToChildrenSearchArrays($animalId, $motherId);
                }

                $generation++;

                //Recursive loop for both parents AFTER increasing the generationCount
                $this->addParents($motherId, $generation);
                $this->addParents($fatherId, $generation);
            }

        }


    }


    /**
     * @param int $childId
     * @param int $parentId
     */
    private function addToChildrenSearchArrays($childId, $parentId)
    {
        //Set parent and children
        if($parentId != null) {
            $this->initializeChildrenSearchArrayKey($parentId);
            $this->childrenSearchArray[$parentId][$childId] = $childId;
        }
    }


    /**
     * @param int $childId
     * @param int $parentId
     */
    private function addToParentsSearchArrays($childId, $parentId)
    {
        //Set parent and children
        if($parentId != null) {
            $this->initializeParentSearchArrayKey($childId);
            $this->parentSearchArray[$childId][$parentId] = $parentId;
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


    /**
     * @param int $animalId
     */
    private function initializeClosedLoopPathsArrayKey($animalId)
    {
        if(!array_key_exists($animalId, $this->closedLoopPaths)) {
            $this->closedLoopPaths[$animalId] = array();
        }
    }


    private function getClosedLoopPaths()
    {
        $animalIds = array_keys($this->childrenSearchArray);

        foreach ($animalIds as $animalId)
        {
            if(count($this->childrenSearchArray[$animalId]) > 1) {
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
        ///Reset paths variable used for the calculation
        $this->paths = array();

        $this->traversChildrenOfCommonAncestor($animalId);

        $pathCenters = array();
        foreach ($this->paths as $path) {
            $pathParts = explode(self::SEPARATOR, $path);
            array_shift($pathParts);
            array_pop($pathParts);
            $pathCenters[] = $pathParts;
        }

        $pathsCount = count($pathCenters);

        for($i = 0; $i < $pathsCount; $i++) {
            for($j = $i+1; $j < $pathsCount; $j++) {

                if($i != $j) { //Just an extra check to be sure
                    $isArraysUnique = Validator::areArrayContentsUnique($pathCenters[$i], $pathCenters[$j]);

                    if($isArraysUnique) {
                        $reveredHalf = array_reverse($pathCenters[$i]);
                        $reveredHalf[] = $animalId;
                        $closedPath = array_merge($reveredHalf,$pathCenters[$j]);

                        $this->initializeClosedLoopPathsArrayKey($animalId);
                        $this->closedLoopPaths[$animalId][] = $closedPath;
                    }
                }
            }
        }
    }


    /**
     * Travers all child branches and save the complete branches or dead ends in the paths class variable.
     *
     * @param int $animalId
     * @param string $path
     */
    private function traversChildrenOfCommonAncestor($animalId, $path = null)
    {
        if($animalId == self::CHILD_ID) {
            //The end of the path has been reached, so register the path
            $this->paths[] = $path;

        } else {

            $childrenArray = $this->childrenSearchArray[$animalId];
            if(count($childrenArray) == 0) {
                //The end of the path has been reached, but it did not end in the childId
                //So ignore this path

            } else {

                foreach ($childrenArray as $childId)
                {
                    if($path == null) {
                        $newPath = $animalId.self::SEPARATOR.$childId;
                    } else {
                        $newPath = $path.self::SEPARATOR.$childId;
                    }
                    $this->traversChildrenOfCommonAncestor($childId, $newPath);
                }

            }
        }
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