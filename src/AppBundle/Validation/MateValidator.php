<?php

namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Util\NullChecker;
use AppBundle\Validation\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class MateValidator
{

    /** @var ObjectManager */
    private $manager;

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client, $validateInConstructor = true)
    {
        $this->manager = $manager;
        if($validateInConstructor) {
            $this->validate($content);
        }
    }

    private function validate($content) {
        
        $eweArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content);
        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);
        
        $this->validateRamArray($ramArray);
        $this->validateEweArray($eweArray);
    }
    
    private function validateRamArray(array $ramArray) {

        //First validate if uln or pedigree exists
        $containsUlnOrPedigree = NullChecker::arrayContainsUlnOrPedigree($ramArray);
        if(!$containsUlnOrPedigree) {
            return false;
        }

        //Then validate the uln if it exists
        $ulnString = NullChecker::getUlnStringFromArray($ramArray, null);
        if ($ulnString != null) {
            return Validator::verifyUlnFormat($ulnString);
        }

        //Validate pedigree if it exists
        return Validator::verifyPedigreeCodeInAnimalArray($this->manager, $ramArray, false);
    }

    private function validateEweArray(array $eweArray) {
        
    }
}