<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientOffspring
{
    const CHILD_ID = -1;
    const GENERATION_OF_ASCENDANTS = 8;
    const GENERATION_DIRECT_PARENTS = 1;
    const NO_INBREEDING = 0;
    const SEPARATOR = ';';
    const DECIMAL_ACCURACY = 5;

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
    private $commonAncestorsInbreedingCoefficient;

    /** @var float */
    private $inbreedingCoefficient;

    /** @var array */
    private $ramData;
    /** @var array */
    private $eweData;
    /** @var array */
    private $animalDataById;
    /** @var array */
    private $ascendants;

    /** @var array */
    private $paths;

    /**
     * InbreedingCoefficientOffspring constructor.
     * @param ObjectManager $em
     * @param array $ramData
     * @param array $eweData
     * @param array $parentSearchArray
     * @param array $childrenSearchArray
     * @param array $animalDataById
     * @param array $ascendants
     */
    public function __construct(ObjectManager $em, $ramData, $eweData, $parentSearchArray = [], $childrenSearchArray = [],
                                $animalDataById, $ascendants = [])
    {
        $this->em = $em;
        $this->animalRepository = $em->getRepository(Animal::class);
        $this->ramData = $ramData;
        $this->eweData = $eweData;
        $this->ascendants = $ascendants;

        $this->closedLoopPaths = array();
        $this->commonAncestorsInbreedingCoefficient = array();
        $this->childrenSearchArray = $childrenSearchArray;
        $this->animalDataById = [];
        $this->parentSearchArray = $parentSearchArray;

        $this->run();
    }


    /**
     * @return float
     */
    public function getValue()
    {
        return $this->inbreedingCoefficient;
    }


    /**
     * @return array
     */
    public function getClosedLoopPaths()
    {
        return $this->closedLoopPaths;
    }


    /**
     * @return array
     */
    public function getCommonAncestorInbreedingCoefficients()
    {
        return $this->commonAncestorsInbreedingCoefficient;
    }


    /**
     * @return float
     */
    private function run()
    {
        if($this->ramData == null || $this->eweData == null) {
            return self::NO_INBREEDING;
        }

        // 1. Set parent and child searchArray values for hypothetical child
        $this->setSearchArrayValuesOfChild();

        if (
            count($this->animalDataById) === 0 ||
            count($this->childrenSearchArray) === 0 ||
            count($this->parentSearchArray) === 0)
        {
            // 2. Traverse parents and create search arrays
            foreach ($this->ascendants as $ascendantsSet) {
                $this->fillAnimalByIdAndChildrenAndParentSearchArrays($ascendantsSet);
            }
        }

        // 3. Find closed loop paths and
        // 4. Recursively calculate the inbreeding coefficients of the common ancestors
        $this->findClosedLoopPaths();

        // 5. Calculate inbreeding coefficient
        $this->calculateInbreedingCoefficient();
    }


    private function calculateInbreedingCoefficient()
    {
        $this->inbreedingCoefficient = 0;
        if(count($this->closedLoopPaths) > 0) {
            $commonAncestorsAnimalId = array_keys($this->commonAncestorsInbreedingCoefficient);
            foreach ($commonAncestorsAnimalId as $commonAncestorAnimalId) {
                $closedLoopPathsOfAncestor = Utils::getNullCheckedArrayValue($commonAncestorAnimalId, $this->closedLoopPaths);
                if(NullChecker::isNotNull($closedLoopPathsOfAncestor)) {
                    foreach ($closedLoopPathsOfAncestor as $closedLoopPath) {
                        $inbreedingCoefficientCommonAncestor = $this->commonAncestorsInbreedingCoefficient[$commonAncestorAnimalId];
                        $animalsInLoop = count($closedLoopPath);
                        $this->inbreedingCoefficient += pow(0.5,$animalsInLoop)*(1+$inbreedingCoefficientCommonAncestor);
                    }
                }
            }
        }
        $this->inbreedingCoefficient = round($this->inbreedingCoefficient, self::DECIMAL_ACCURACY);
    }


    private function setSearchArrayValuesOfChild()
    {
        $fatherId = $this->ramData['id'];
        $motherId = $this->eweData['id'];

        $this->initializeChildrenSearchArrayKey($fatherId);
        $this->initializeChildrenSearchArrayKey($motherId);
        $this->childrenSearchArray[$fatherId][self::CHILD_ID] = self::CHILD_ID;
        $this->childrenSearchArray[$motherId][self::CHILD_ID] = self::CHILD_ID;

        $this->initializeParentSearchArrayKey(self::CHILD_ID);
        $this->parentSearchArray[self::CHILD_ID][$fatherId] = $fatherId;
        $this->parentSearchArray[self::CHILD_ID][$motherId] = $motherId;
    }


    /**
     * @param $ascendantsSet
     */
    private function fillAnimalByIdAndChildrenAndParentSearchArrays($ascendantsSet)
    {
        if (!is_array($ascendantsSet) || count($ascendantsSet) === 0) {
            return;
        }

        $animalId = ArrayUtil::get('id', $ascendantsSet, null);

        if (is_int($animalId) && is_array($ascendantsSet)) {
            if (!key_exists($animalId, $this->animalDataById)) {

                foreach (array_keys($ascendantsSet) as $key) {
                    if ($key !== 'father' && $key !== 'mother') {
                        $this->animalDataById[$animalId][$key] = $ascendantsSet[$key];
                    }
                }
            }
        }

        foreach (['father','mother'] as $parentKey) {

            $parentArray = ArrayUtil::get($parentKey, $ascendantsSet, []);
            $parentId = ArrayUtil::get('id', $parentArray);

            $this->addToChildrenSearchArrays($animalId, $parentId);
            $this->addToParentsSearchArrays($animalId, $parentId);

            $this->fillAnimalByIdAndChildrenAndParentSearchArrays($parentArray);
        }
    }


    /**
     * @param int $childId
     * @param int $parentId
     */
    private function addToChildrenSearchArrays($childId, $parentId)
    {
        //Set parent and children
        if($parentId !== null && $childId !== null) {
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
        if($parentId != null && $childId !== null) {
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


    private function findClosedLoopPaths()
    {
        $animalIds = array_keys($this->childrenSearchArray);

        foreach ($animalIds as $animalId)
        {
            if(count($this->childrenSearchArray[$animalId]) > 1) {
                $this->getClosedLoopPathsOfAnimal($animalId);

                if (key_exists($animalId, $this->animalDataById)) {
                    //Calculate the inbreeding coefficients of all common ancestors
                    $commonAncestorInbreedingCoefficientResult =
                        new InbreedingCoefficient(
                            $this->em,
                            $this->animalDataById[$animalId],
                            $this->parentSearchArray,
                            $this->childrenSearchArray,
                            $this->animalDataById
                    );
                    $this->commonAncestorsInbreedingCoefficient[$animalId] = $commonAncestorInbreedingCoefficientResult->getValue();
                }
            }
        }
    }


    /**
     * @param $possibleCommonAncestorAnimalId
     */
    private function getClosedLoopPathsOfAnimal($possibleCommonAncestorAnimalId)
    {
        ///Reset paths variable used for the calculation
        $this->paths = array();

        $this->traversChildrenOfCommonAncestor($possibleCommonAncestorAnimalId);

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
                        $reveredHalf[] = $possibleCommonAncestorAnimalId;
                        $closedPath = array_merge($reveredHalf,$pathCenters[$j]);

                        $this->initializeClosedLoopPathsArrayKey($possibleCommonAncestorAnimalId);
                        $this->closedLoopPaths[$possibleCommonAncestorAnimalId][] = $closedPath;
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

            $childrenArray = Utils::getNullCheckedArrayValue($animalId, $this->childrenSearchArray);
            if(NullChecker::isNull($childrenArray)) {
                //See comment below
            } elseif(count($childrenArray) == 0) {
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
     * @param array $animalData
     * @param array $parentSearchArray
     * @param array $childrenSearchArray
     * @param array $animalDataById
     */
    public function __construct(ObjectManager $em, $animalData, $parentSearchArray, $childrenSearchArray, $animalDataById)
    {
        $ramData = ArrayUtil::get('father', $animalData, []);
        $eweData = ArrayUtil::get('mother', $animalData, []);

        $this->inbreedingCoefficientOffspring = new InbreedingCoefficientOffspring($em, $ramData, $eweData,
            $parentSearchArray, $childrenSearchArray, $animalDataById);
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->inbreedingCoefficientOffspring->getValue();
    }

}