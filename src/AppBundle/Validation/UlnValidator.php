<?php

namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Monolog\Handler\Curl\Util;
use Symfony\Component\HttpFoundation\JsonResponse;

class UlnValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'ANIMAL IS NOT REGISTERED WITH GIVEN CLIENT';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'ULN OF ANIMAL IS VALID';
    const MISSING_INPUT_CODE = 428;
    const MISSING_INPUT_MESSAGE = 'NO ULN GIVEN';
    const MISSING_ANIMAL_CODE = 428;
    const MISSING_ANIMAL_MESSAGE = 'ANIMAL IS NOT REGISTERED WITH NSFO';

    /** @var boolean */
    private $isUlnSetValid;

    /** @var boolean */
    private $isInputMissing;

    /** @var boolean */
    private $isInDatabase;

    /** @var string */
    private $ulnCountryCode;

    /** @var string */
    private $ulnNumber;

    /** @var int */
    private $numberOfAnimals;

    /** @var ArrayCollection */
    private $locations;

    /** @var EntityManager */
    private $manager;

    /**
     * UbnValidator constructor.
     * @param EntityManager $manager
     * @param Client $client
     * @param Collection $content
     * @param boolean $multipleAnimals
     */
    public function __construct(EntityManager $manager, Collection $content, $multipleAnimals = false, Client $client = null)
    {
        $this->manager = $manager;

        if($client != null) {
            $this->locations = $manager->getRepository(Location::class)->findAllLocationsOfClient($client);
        }

        $animalArray = null;
        $this->isInputMissing = true;
        $this->isUlnSetValid = false;
        $this->isInDatabase = true;
        $this->numberOfAnimals = 0;

        if($multipleAnimals == false) {

            $animalArray = null;
            foreach ($content->getKeys() as $key) {
                if($key == Constant::ANIMAL_NAMESPACE) {
                    $animalArray = $content->get($key);

                    $this->isInputMissing = false;
                    $this->isUlnSetValid = true;

                    $this->isUlnSetValid = $this->validateUlnInput($animalArray, $client);
                    $this->numberOfAnimals++;
                }
            }

        } else {

            $animalArrays = null;
            foreach ($content->getKeys() as $key) {
                if($key == Constant::ANIMALS_NAMESPACE) {
                    $animalArrays = $content->get($key);

                    $this->isInputMissing = false;
                    $this->isUlnSetValid = true;

                    foreach ($animalArrays as $animalArray) {
                        $isUlnValid = $this->validateUlnInput($animalArray, $client);

                        if(!$isUlnValid) {
                            $this->isUlnSetValid = false;
                        }
                        $this->numberOfAnimals++;
                    }
                }
            }
        }
        if($this->numberOfAnimals == 0) {
            $this->isInputMissing = true;
            $this->isUlnSetValid = false;
        }

    }

    /**
     * @param array $animalArray
     * @param Client $client
     * @return boolean
     */
    private function validateUlnInput($animalArray, $client)
    {
        $ulnExists = array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray) &&
            array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $animalArray);

        if ($ulnExists) {
            $numberToCheck = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
            $countryCodeToCheck = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
            $this->ulnCountryCode = $countryCodeToCheck;
            $this->ulnNumber = $numberToCheck;
        } else {
            $this->isInputMissing = true;
            return false;
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ulnCountryCode', $countryCodeToCheck))
            ->andWhere(Criteria::expr()->eq('ulnNumber', $numberToCheck));

        $animal = $this->manager->getRepository(Animal::class)
            ->matching($criteria)->first();

        //First verify if animal actually exists
        if($animal == null) {
            $this->isInDatabase = false;
            return false;
        }

        if($client == null) { //Get any animals regardless of client
            return true;

        } else { //Get only animals owned by client

            foreach ($this->locations as $location) {
                if($animal->getLocation() == $location) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Only create this JsonResponse when there actually are errors.
     *
     * @return JsonResponse
     */
    public function createArrivalJsonErrorResponse()
    {
        $uln = null;

        if($this->isUlnSetValid) {
            $code = self::VALID_CODE;
            $message = self::VALID_MESSAGE;
        } else {
            $code = self::ERROR_CODE;
            $message = self::ERROR_MESSAGE;
        }

        //Only return the values for the identification type being tested
        if (!$this->isInputMissing && $this->isInDatabase) {
            $uln = $this->ulnCountryCode . $this->ulnNumber;
        } else if (!$this->isInDatabase) {
            $code = self::MISSING_ANIMAL_CODE;
            $message = self::MISSING_ANIMAL_MESSAGE;
            $uln = $this->ulnCountryCode . $this->ulnNumber;
        } else { //If no ULN or PEDIGREE found
            $code = self::MISSING_INPUT_CODE;
            $message = self::MISSING_INPUT_MESSAGE;
        }

        $result = array(
            Constant::CODE_NAMESPACE => $code,
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::ULN_NAMESPACE => $uln,
            );

        return new JsonResponse($result, $code);
    }


    /**
     * @return bool
     */
    public function getIsUlnSetValid() {
        return $this->isUlnSetValid;
    }

    /**
     * @return int
     */
    public function getNumberOfAnimals()
    {
        return $this->numberOfAnimals;
    }

    /**
     * @return string
     */
    public function getUlnCode()
    {
        return $this->ulnCountryCode . $this->ulnNumber;
    }


}