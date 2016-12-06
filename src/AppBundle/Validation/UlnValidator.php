<?php

namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\IsNull;

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

    const MAX_ANIMALS = 50;
    const ERROR_MESSAGE_MAX_ANIMALS_EXCEEDED = 'NO MORE THAN 50 ANIMALS CAN BE SELECTED AT A TIME';

    /** @var boolean */
    private $isUlnSetValid;

    /** @var boolean */
    private $isInputMissing;

    /** @var boolean */
    private $isAnimalCountWithinLimit;

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

    /** @var ObjectManager */
    private $manager;

    /** @var array */
    private $ulns;

    /** @var boolean */
    private $allowAllAnimals;

    /**
     * UbnValidator constructor.
     * @param ObjectManager $manager
     * @param Client $client
     * @param Collection $content
     * @param boolean $multipleAnimals
     * @param Location $location
     */
    public function __construct(ObjectManager $manager, Collection $content, $multipleAnimals = false, Client $client = null, $location)
    {
        $this->manager = $manager;

        $this->ulns = [];
        $this->allowAllAnimals = false;
        if($client != null) {
            /** @var LocationRepository $locationRepository */
            $locationRepository = $manager->getRepository(Location::class);
            $this->locations = $locationRepository->findAllLocationsOfClient($client);
            $this->fillUlnSearchArrayOfHistoricAnimalsAllLocations();
        } elseif ($location != null) {
            $this->locations = new ArrayCollection();
            $this->locations->add($location);
            $this->fillUlnSearchArrayOfHistoricAnimalsAllLocations();
        } else {
            //If both client and location are null
            $this->allowAllAnimals = true;
        }

        $animalArray = null;
        $this->isInputMissing = true;
        $this->isUlnSetValid = false;
        $this->isInDatabase = true;
        $this->isAnimalCountWithinLimit = true;
        $this->numberOfAnimals = 0;

        if($multipleAnimals == false) {

            $animalArray = null;
            foreach ($content->getKeys() as $key) {
                if($key == Constant::ANIMAL_NAMESPACE) {
                    $animalArray = $content->get($key);

                    $this->isInputMissing = false;
                    $this->isUlnSetValid = true;

                    $this->isUlnSetValid = $this->validateUlnInput($animalArray, $client, $location);
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
                    $this->numberOfAnimals = count($animalArrays);

                    if($this->numberOfAnimals > self::MAX_ANIMALS) {
                        $this->isAnimalCountWithinLimit = false;
                        $this->isUlnSetValid = false;
                    } else {
                        foreach ($animalArrays as $animalArray) {
                            $isUlnValid = $this->validateUlnInput($animalArray, $client, $location);
                            if(!$isUlnValid) {
                                $this->isUlnSetValid = false;
                            }
                        }
                    }
                }
            }
        }
        if($this->numberOfAnimals == 0) {
            $this->isInputMissing = true;
            $this->isUlnSetValid = false;
        }

    }


    private function fillUlnSearchArrayOfHistoricAnimalsAllLocations()
    {
        /** @var Location $location */
        foreach ($this->locations as $location) {
            $sql = "SELECT CONCAT(a.uln_country_code, a.uln_number) as uln
            FROM animal a
              INNER JOIN location l ON a.location_id = l.id
            WHERE a.location_id = ".$location->getId()."
            UNION
            SELECT CONCAT(a.uln_country_code, a.uln_number) as uln
            FROM animal_residence r
              INNER JOIN animal a ON r.animal_id = a.id
              LEFT JOIN location l ON a.location_id = l.id
              LEFT JOIN company c ON c.id = l.company_id
            WHERE r.location_id = ".$location->getId()." AND (c.is_reveal_historic_animals = TRUE OR a.location_id ISNULL)";
            $retrievedAnimalData = $this->manager->getConnection()->query($sql)->fetchAll();

            foreach ($retrievedAnimalData as $animalData) {
                $uln = $animalData['uln'];
                $this->ulns[$uln] = $uln;
            }
        }
    }


    /**
     * @param array $animalArray
     * @param Client $client
     * @param Location $location
     * @return boolean
     */
    private function validateUlnInput($animalArray, $client, $location)
    {
        $ulnExists = array_key_exists(JsonInputConstant::ULN_COUNTRY_CODE, $animalArray) &&
            array_key_exists(JsonInputConstant::ULN_NUMBER, $animalArray);

        if ($ulnExists) {
            $numberToCheck = $animalArray[JsonInputConstant::ULN_NUMBER];
            $countryCodeToCheck = $animalArray[JsonInputConstant::ULN_COUNTRY_CODE];
            $this->ulnCountryCode = $countryCodeToCheck;
            $this->ulnNumber = $numberToCheck;
        } else {
            $this->isInputMissing = true;
            return false;
        }

        if(!is_string($countryCodeToCheck) || !is_string($numberToCheck)) { return false; }

        if(!$this->allowAllAnimals) {
            //First check if the animals is a historic animal
            if(array_key_exists($countryCodeToCheck.$numberToCheck, $this->ulns)) {
                return true;
            }
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('ulnCountryCode', $countryCodeToCheck))
            ->andWhere(Criteria::expr()->eq('ulnNumber', $numberToCheck))
            ->andWhere(Criteria::expr()->eq('location', $location ));

        $results = $this->manager->getRepository(Animal::class)
            ->matching($criteria);

        //First verify if animal actually exists
        if(count($results) == 0) {
            $this->isInDatabase = false;
            return false;

        } else {
            //If animal exists, get any animals regardless of client
            return true;
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

        if(!$this->isAnimalCountWithinLimit) {
            $code = self::ERROR_CODE;
            $message = self::ERROR_MESSAGE_MAX_ANIMALS_EXCEEDED;
        } else if($this->isUlnSetValid) {
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