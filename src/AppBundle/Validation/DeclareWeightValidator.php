<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Weight;
use AppBundle\Entity\WeightRepository;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use AppBundle\Constant\Constant;
use Doctrine\Common\Persistence\ObjectManager;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class WeightValidator
 * @package AppBundle\Validation
 */
class DeclareWeightValidator extends DeclareNsfoBaseValidator
{
    const DEFAULT_MAX_WEIGHT = 200.00; //NOTE CHANGING THIS WILL AFFECT THE FRONT-END!
    const DEFAULT_MIN_WEIGHT = 0.00;   //NOTE CHANGING THIS WILL AFFECT THE FRONT-END!

    const MEASUREMENT_ALREADY_EXISTS = "ANIMAL ALREADY HAS A WEIGHT MEASUREMENT ON THIS DATE. EDIT, OR CHOOSE ANOTHER DATE";

    const MEASUREMENT_DATE_MISSING = "MEASUREMENT DATE IS MISSING";
    const WEIGHT_IS_MISSING  = "WEIGHT IS MISSING";

    const WEIGHT_IS_TOO_LOW  = "WEIGHT IS TOO LOW. IT SHOULD BE AT LEAST ".self::DEFAULT_MIN_WEIGHT.' KG';
    const WEIGHT_IS_TOO_HIGH = "WEIGHT IS TOO HIGH. IT CANNOT EXCEED ".self::DEFAULT_MAX_WEIGHT.' KG';
    const MEASUREMENT_DATE_IN_FUTURE = "MEASUREMENT DATE CANNOT BE IN THE FUTURE";

    const ANIMAL_MISSING_INPUT = 'ANIMAL: NO ULN GIVEN';
    const ANIMAL_NOT_FOUND = "ANIMAL: NOT FOUND";
    const ANIMAL_NOT_OF_CLIENT = 'FOUND ANIMAL DOES NOT BELONG TO CLIENT';

    const MESSAGE_ID_ERROR     = 'MESSAGE ID: NO DECLARE WEIGHT FOUND FOR GIVEN MESSAGE ID AND CLIENT';
    const MESSAGE_OVERWRITTEN     = 'MESSAGE ID: DECLARE WEIGHT IS ALREADY OVERWRITTEN';

    /** @var float */
    private $minWeight;

    /** @var float  */
    private $maxWeight;

    /** @var DeclareWeight */
    private $declareWeight;

    /** @var Animal */
    private $animal;

    /** @var \DateTime */
    private $measurementDate;

    /**
     * DeclareWeightValidator constructor.
     * @param ObjectManager $manager
     * @param ArrayCollection $content
     * @param Client $client
     * @param bool $isPost
     * @param float $minWeight
     * @param float $maxWeight
     */
    public function __construct(ObjectManager $manager, ArrayCollection $content, Client
    $client, $isPost = true,
                                $minWeight = DeclareWeightValidator::DEFAULT_MIN_WEIGHT,
                                $maxWeight = DeclareWeightValidator::DEFAULT_MAX_WEIGHT)
    {
        parent::__construct($manager, $content, $client);
        
        //Set given values
        $this->minWeight = $minWeight;
        $this->maxWeight = $maxWeight;

        if($isPost) {
            $this->validatePost($content);
        } else {
            $this->validateEdit($content);
        }
    }

    /**
     * @return DeclareWeight
     */
    public function getDeclareWeightFromMessageId()
    {
        return $this->declareWeight;
    }

    /**
     * @param ArrayCollection $content
     */
    private function validatePost($content) {
        $animalArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content);

        $isAnimalInputValid = $this->validateAnimalArray($animalArray);
        $isNonAnimalInputValid = $this->validateNonAnimalValues($content);
        $isMeasurementAlreadyExists = $this->validateIfMeasurementDoesNotExistsYet();

        if($isAnimalInputValid && $isNonAnimalInputValid && $isMeasurementAlreadyExists) {
            $this->isInputValid = true;
        } else {
            $this->isInputValid = false;
        }
    }

    /**
     * @param ArrayCollection $content
     */
    private function validateEdit($content) {

        //Default
        $this->isInputValid = true;

        //Validate MessageId First

        $messageId = $content->get(JsonInputConstant::MESSAGE_ID);

        $foundDeclareWeight = $this->isNonRevokedNsfoDeclarationOfClient($messageId);
        if(!($foundDeclareWeight instanceof DeclareWeight)) {
            $this->errors[] = self::MESSAGE_ID_ERROR;
            $isMessageIdValid = false;
        } else {
            $this->declareWeight = $foundDeclareWeight;
            $isMessageIdValid = true;

            $isNotOverwritten = $this->validateNsfoDeclarationIsNotAlreadyOverwritten($foundDeclareWeight);
            if(!$isNotOverwritten) {
                $this->isInputValid = false;
            }
        }

        //Validate content second

        $animalArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::ANIMAL, $content);

        $isAnimalInputValid = $this->validateAnimalArray($animalArray);
        $isNonAnimalInputValid = $this->validateNonAnimalValues($content);

        if(!$isAnimalInputValid || !$isNonAnimalInputValid || !$isMessageIdValid) {
            $this->isInputValid = false;
        }
        
    }

    /**
     * @param array $animalArray
     * @return bool
     */
    private function validateAnimalArray($animalArray) {

        $ulnString = NullChecker::getUlnStringFromArray($animalArray, null);
        if($ulnString == null) {
            $this->errors[] = self::ANIMAL_MISSING_INPUT;
            return false;
        }

        $foundAnimal = $this->animalRepository->findAnimalByAnimalArray($animalArray);
        if($foundAnimal == null) {
            $this->errors[] = self::ANIMAL_NOT_FOUND;
            return false;
        }

        $isOwnedByClient = Validator::isAnimalOfClient($foundAnimal, $this->client);
        if($isOwnedByClient) {
            $this->animal = $foundAnimal;
            return true;
        } else {
            $this->errors[] = self::ANIMAL_NOT_OF_CLIENT;
            return false;
        }
    }


    /**
     * @param ArrayCollection $content
     * @return bool
     */
    private function validateNonAnimalValues($content)
    {
        $isValid = true;

        $weight = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::WEIGHT, $content);
        $measurementDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MEASUREMENT_DATE, $content);

        if($weight == null) {
            $this->errors[] = self::WEIGHT_IS_MISSING;
            $isValid = false;
        } else {
            if($weight < $this->minWeight) {
                $this->errors[] = self::WEIGHT_IS_TOO_LOW;
                $isValid = false;
            }

            if($weight > $this->maxWeight) {
                $this->errors[] = self::WEIGHT_IS_TOO_HIGH;
                $isValid = false;
            }
        }

        if($measurementDate == null) {
            $this->errors[] = self::MEASUREMENT_DATE_MISSING;
            $isValid = false;

        } elseif($measurementDate > new \DateTime('now')) {
            $this->errors[] = self::MEASUREMENT_DATE_IN_FUTURE;
            $isValid = false;

        } else {
            $this->measurementDate = $measurementDate;
        }

        return $isValid;
    }


    /**
     * @return bool
     */
    private function validateIfMeasurementDoesNotExistsYet()
    {
        if($this->measurementDate != null) {
            /** @var WeightRepository $weightRepository */
            $weightRepository = $this->manager->getRepository(Weight::class);

            $isMeasurementAlreadyExists = $weightRepository->isExistForAnimalOnDate($this->animal, $this->measurementDate);
            if($isMeasurementAlreadyExists) {
                $this->errors[] = self::MEASUREMENT_ALREADY_EXISTS;
                return false;
            } else {
                return true;
            }
        }
    }



}