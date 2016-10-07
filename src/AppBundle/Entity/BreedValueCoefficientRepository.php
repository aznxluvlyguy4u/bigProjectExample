<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedTraitCoefficient;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\BreedIndexType;
use AppBundle\Enumerator\BreedTrait;
use AppBundle\Util\NumberUtil;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class BreedValueCoefficient
 * @package AppBundle\Entity
 */
class BreedValueCoefficientRepository extends BaseRepository {
    
    public function generateLambMeatIndexCoefficients()
    {
        $em = $this->getManager();
        
        $hasSomethingChanged = false;
        
        $searchArray = new ArrayCollection();
        $searchArray->set(BreedTrait::GROWTH, BreedTraitCoefficient::LAMB_MEAT_INDEX_GROWTH);
        $searchArray->set(BreedTrait::FAT, BreedTraitCoefficient::LAMB_MEAT_INDEX_FAT);
        $searchArray->set(BreedTrait::MUSCLE_THICKNESS, BreedTraitCoefficient::LAMB_MEAT_INDEX_MUSCLE_THICKNESS);
        //Add new trait-coefficient pairs here
        
        $traits = $searchArray->getKeys();
        
        foreach ($traits as $trait) {
            $coefficientValue = $searchArray->get($trait);

            /** @var BreedValueCoefficient $coefficient */
            $coefficient = $this->findOneBy(['indexType' => BreedIndexType::LAMB_MEAT_INDEX, 'trait' => $trait]);

            if($coefficient == null) {
                //Generate new coefficient
                $coefficient = new BreedValueCoefficient(BreedIndexType::LAMB_MEAT_INDEX, $trait, $coefficientValue);
                $em->persist($coefficient);
                $hasSomethingChanged = true;

            } elseif(NumberUtil::areFloatsEqual($coefficient->getValue(), $coefficientValue)) {
                //Update the value
                $coefficient->setValue($coefficientValue);
                $em->persist($coefficient);
                $hasSomethingChanged = true;
            }
        }

        if($hasSomethingChanged) { $em->flush(); }
    }

}