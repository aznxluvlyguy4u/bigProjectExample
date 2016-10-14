<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedTraitCoefficient;
use AppBundle\Enumerator\BreedValueCoefficientType;
use AppBundle\Enumerator\BreedTrait;
use AppBundle\Util\BreedValueUtil;
use AppBundle\Util\NumberUtil;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class BreedValueCoefficient
 * @package AppBundle\Entity
 */
class BreedValueCoefficientRepository extends BaseRepository {
    
    public function generateLambMeatIndexCoefficients()
    {        
        $valueCoefficients = new ArrayCollection();
        $valueCoefficients->set(BreedTrait::GROWTH, BreedTraitCoefficient::LAMB_MEAT_INDEX_GROWTH);
        $valueCoefficients->set(BreedTrait::FAT, BreedTraitCoefficient::LAMB_MEAT_INDEX_FAT);
        $valueCoefficients->set(BreedTrait::MUSCLE_THICKNESS, BreedTraitCoefficient::LAMB_MEAT_INDEX_MUSCLE_THICKNESS);
        //Add new trait-coefficient pairs here

        $geneticVarianceCoefficients = new ArrayCollection();
        $geneticVarianceCoefficients->set(BreedTrait::GROWTH, BreedTraitCoefficient::LAMB_MEAT_INDEX_GROWTH_GENETIC_VARIANCE);
        $geneticVarianceCoefficients->set(BreedTrait::FAT, BreedTraitCoefficient::LAMB_MEAT_INDEX_FAT_GENETIC_VARIANCE);
        $geneticVarianceCoefficients->set(BreedTrait::MUSCLE_THICKNESS, BreedTraitCoefficient::LAMB_MEAT_INDEX_MUSCLE_THICKNESS_GENETIC_VARIANCE);
        //Add new trait-coefficient pairs here


        $traits = $valueCoefficients->getKeys();
        
        foreach ($traits as $trait) {

            $valueCoefficient = $valueCoefficients->get($trait);
            $this->generateLambMeatIndexCoefficient(BreedValueCoefficientType::LAMB_MEAT_INDEX, $trait, $valueCoefficient);

            $geneticVarianceCoefficient = $geneticVarianceCoefficients->get($trait);
            $this->generateLambMeatIndexCoefficient(BreedValueCoefficientType::LAMB_MEAT_INDEX_GENETIC_VARIANCE, $trait, $geneticVarianceCoefficient);

            $accuracyCoefficient = BreedValueUtil::calculateLambMeatIndexAccuracyCoefficient($valueCoefficient, $geneticVarianceCoefficient);
            $this->generateLambMeatIndexCoefficient(BreedValueCoefficientType::LAMB_MEAT_INDEX_ACCURACY, $trait, $accuracyCoefficient);
        }
    }


    /**
     * @param string $indexType
     * @param string $trait
     * @param float $value
     */
    private function generateLambMeatIndexCoefficient($indexType, $trait, $value)
    {
        $em = $this->getManager();

        /** @var BreedValueCoefficient $coefficient */
        $coefficientEntity = $this->findOneBy(['indexType' => $indexType, 'trait' => $trait]);

        if($coefficientEntity == null) {
            //Generate new coefficient
            $coefficientEntity = new BreedValueCoefficient($indexType, $trait, $value);
            $em->persist($coefficientEntity);
            $em->flush();

        } elseif(!NumberUtil::areFloatsEqual($coefficientEntity->getValue(), $value)) {
            //Update the value
            $coefficientEntity->setValue($value);
            $em->persist($coefficientEntity);
            $em->flush();
        }
    }


    /**
     * @return array|null
     */
    public function getLambMeatIndexCoefficients()
    {
        return $this->getLambMeatIndexCoefficientsByIndexType(BreedValueCoefficientType::LAMB_MEAT_INDEX);
    }


    /**
     * @return array|null
     */
    public function getLambMeatIndexAccuracyCoefficients()
    {
        return $this->getLambMeatIndexCoefficientsByIndexType(BreedValueCoefficientType::LAMB_MEAT_INDEX_ACCURACY);
    }


    /**
     * @return array|null
     */
    private function getLambMeatIndexCoefficientsByIndexType($indexType)
    {
        $breedValueCoefficients = $this->findBy(['indexType' => $indexType]);

        if(count($breedValueCoefficients) == 0) { return null; }

        $results = array();
        /** @var BreedValueCoefficient $breedValueCoefficient */
        foreach ($breedValueCoefficients as $breedValueCoefficient) {
            $results[$breedValueCoefficient->getTrait()] = $breedValueCoefficient->getValue();
        }
        return $results;
    }
}