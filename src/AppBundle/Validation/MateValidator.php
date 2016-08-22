<?php

namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class MateValidator
{
    const ERROR_CODE = 401;
    const ERROR_MESSAGE = 'INVALID INPUT';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'OK';

    /** @var boolean */
    private $validateEweGender;

    /** @var boolean */
    private $isInputValid;

    /** @var AnimalRepository */
    private $animalRepository;

    /** @var ObjectManager */
    private $manager;

    /** @var Client */
    private $client;

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client, $validateEweGender = true)
    {
        $this->manager = $manager;
        $this->animalRepository = $this->manager->getRepository(Animal::class);
        $this->client = $client;
        $this->isInputValid = false;
        $this->validateEweGender = $validateEweGender;

        $this->validate($content);
    }

    /**
     * @param ArrayCollection $content
     */
    private function validate($content) {
        
        $eweArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content);
        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);
        
        $isRamInputValid = $this->validateRamArray($ramArray);
        $isEweInputValid = $this->validateEweArray($eweArray);
        $isNonAnimalInputNotEmpty = $this->validateIfNonAnimalValuesAreNotEmpty($content);

        if($isRamInputValid && $isEweInputValid && $isNonAnimalInputNotEmpty) {
            $this->isInputValid = true;
        } else {
            $this->isInputValid = false;
        }
    }

    /**
     * @return bool
     */
    public function getIsInputValid()
    {
        return $this->isInputValid;
    }

    /**
     * @param array $ramArray
     * @return bool
     */
    private function validateRamArray($ramArray) {

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

    /**
     * @param array $eweArray
     * @return bool
     */
    private function validateEweArray($eweArray) {
        
        $foundAnimal = $this->animalRepository->findAnimalByAnimalArray($eweArray);
        if($foundAnimal == null) {
            return false;
        }

        if($this->validateEweGender) {
            if(!($foundAnimal instanceof Ewe)) {
                return false;
            }
        }
        
        return Validator::isAnimalOfClient($foundAnimal, $this->client);
    }


    /**
     * @param ArrayCollection $content
     * @return bool
     */
    private function validateIfNonAnimalValuesAreNotEmpty($content)
    {
        $startDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::START_DATE, $content);
        $endDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::END_DATE, $content);
        $ki = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::KI, $content);
        $pmsg = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::PMSG, $content);

        if($startDate === null || $endDate === null || $ki === null || $pmsg === null) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * @return JsonResponse
     */
    public function createJsonResponse()
    {
        if($this->isInputValid){
            $message = self::VALID_MESSAGE;
            $code = self::VALID_CODE;
        } else {
            $message = self::ERROR_MESSAGE;
            $code = self::ERROR_CODE;
        }

        $result = array(
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::CODE_NAMESPACE => $code);

        return new JsonResponse($result, $code);
    }
}