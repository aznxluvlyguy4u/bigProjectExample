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
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class MateValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'INVALID INPUT';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'OK';

    const RAM_MISSING_INPUT               = 'STUD RAM: NO ULN OR PEDIGREE GIVEN';
    const RAM_ULN_FORMAT_INCORRECT        = 'STUD RAM: ULN FORMAT INCORRECT';
    const RAM_ULN_FOUND_BUT_NOT_MALE      = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN ULN IS NOT MALE';
    const RAM_PEDIGREE_FOUND_BUT_NOT_MALE = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN PEDIGREE IS NOT MALE';
    const RAM_PEDIGREE_NOT_FOUND          = 'STUD RAM: NO ANIMAL FOUND FOR GIVEN PEDIGREE';

    const EWE_MISSING_INPUT     = 'STUD EWE: NO ULN GIVEN';
    const EWE_NO_ANIMAL_FOUND   = 'STUD EWE: NO ANIMAL FOUND FOR GIVEN ULN';
    const EWE_FOUND_BUT_NOT_EWE = 'STUD EWE: ANIMAL WAS FOUND FOR GIVEN ULN, BUT WAS NOT AN EWE ENTITY';
    const EWE_NOT_OF_CLIENT     = 'STUD EWE: FOUND EWE DOES NOT BELONG TO CLIENT';

    const MESSAGE_ID_ERROR     = 'MESSAGE ID: NO MATE FOUND FOR GIVEN MESSAGE ID AND CLIENT';
    const MATE_OVERWRITTEN     = 'MESSAGE ID: MATE IS ALREADY OVERWRITTEN';

    const START_DATE_MISSING   = 'START DATE MISSING';
    const END_DATE_MISSING     = 'END DATE MISSING';
    const START_DATE_IN_FUTURE = 'START DATE CANNOT BE IN THE FUTURE';
    const END_DATE_IN_FUTURE   = 'END DATE CANNOT BE IN THE FUTURE';
    const START_AFTER_END_DATE = 'START DATE CANNOT BE AFTER END DATE';

    const KI_MISSING         = 'KI MISSING';
    const PMSG_MISSING       = 'PMSG MISSING';

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

    /** @var Mate */
    private $mate;

    /** @var array */
    private $errors;

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client, $validateEweGender = true, $isPost = true)
    {
        $this->manager = $manager;
        $this->animalRepository = $this->manager->getRepository(Animal::class);
        $this->client = $client;
        $this->isInputValid = false;
        $this->validateEweGender = $validateEweGender;
        $this->errors = array();

        if($isPost) {
            $this->validatePost($content);   
        } else {
            $this->validateEdit($content);
        }
    }

    /**
     * @param ArrayCollection $content
     */
    private function validatePost($content) {
        
        $eweArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content);
        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);
        
        $isRamInputValid = $this->validateRamArray($ramArray);
        $isEweInputValid = $this->validateEweArray($eweArray);
        $isNonAnimalInputValid = $this->validateNonAnimalValues($content);

        if($isRamInputValid && $isEweInputValid && $isNonAnimalInputValid) {
            $this->isInputValid = true;
        } else {
            $this->isInputValid = false;
        }
    }


    /**
     * @return Mate
     */
    public function getMateFromMessageId()
    {
        return $this->mate;
    }

    /**
     * @param ArrayCollection $content
     */
    private function validateEdit($content)
    {
        //Default
        $this->isInputValid = true;

        //Validate MessageId First

        $messageId = $content->get(JsonInputConstant::MESSAGE_ID);

        $foundMate = self::isNonRevokedMateOfClient($this->manager, $this->client, $messageId);
        if(!($foundMate instanceof Mate)) {
            $this->errors[] = self::MESSAGE_ID_ERROR;
            $isMessageIdValid = false;
        } else {
            $this->mate = $foundMate;
            $isMessageIdValid = true;

            $isNotOverwritten = $this->validateMateIsNotAlreadyOverwritten($foundMate);
            if(!$isNotOverwritten) {
                $this->isInputValid = false;
            }
        }

        //Validate content second

        $eweArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWE, $content);
        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);

        $isRamInputValid = $this->validateRamArray($ramArray);
        $isEweInputValid = $this->validateEweArray($eweArray);
        $isNonAnimalInputValid = $this->validateNonAnimalValues($content);

        if(!$isRamInputValid || !$isEweInputValid || !$isNonAnimalInputValid || !$isMessageIdValid) {
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
            $this->errors[] = self::RAM_MISSING_INPUT;
            return false;
        }

        //Then validate the uln if it exists
        $ulnString = NullChecker::getUlnStringFromArray($ramArray, null);
        if ($ulnString != null) {
            //ULN check
            
            $isUlnFormatValid = Validator::verifyUlnFormat($ulnString);
            if(!$isUlnFormatValid) {
                $this->errors[] = self::RAM_ULN_FORMAT_INCORRECT;
                return false;
            }
            
            //If animal is in database, verify the gender
            $animal = $this->animalRepository->findAnimalByUlnString($ulnString);
            $isMaleCheck = $this->validateIfRamUlnBelongsToMaleIfFoundInDatabase($animal);
            if(!$isMaleCheck) {
                $this->errors[] = self::RAM_ULN_FOUND_BUT_NOT_MALE;
            }
            return $isMaleCheck;
            
        } else {
            //Validate pedigree if it exists (by checking if animal is in the database or not)
            $pedigreeCodeExists = Validator::verifyPedigreeCodeInAnimalArray($this->manager, $ramArray, false);
            if($pedigreeCodeExists) {
                //If animal is in database, verify the gender
                $animal = $this->animalRepository->findAnimalByAnimalArray($ramArray);
                $isMaleCheck = $this->validateIfRamUlnBelongsToMaleIfFoundInDatabase($animal);
                if(!$isMaleCheck) {
                    $this->errors[] = self::RAM_PEDIGREE_FOUND_BUT_NOT_MALE;
                }
                return $isMaleCheck;
            } else {
                $this->errors[] = self::RAM_PEDIGREE_NOT_FOUND;
                return false;
            }
        }
    }


    /**
     * @param Animal $animal
     * @return bool
     */
    private function validateIfRamUlnBelongsToMaleIfFoundInDatabase($animal)
    {
        if($animal != null) {
            //If animal is in database, verify the gender
            $isAnimalMale = Validator::isAnimalMale($animal);
            if($isAnimalMale) {
                return true;
            } else {
                return false;
            }
        } else {
            //If animal is not in database, it cannot be verified. So just let it pass.
            return true;
        }
    }


    /**
     * @param array $eweArray
     * @return bool
     */
    private function validateEweArray($eweArray) {

        $ulnString = NullChecker::getUlnStringFromArray($eweArray, null);
        if($ulnString == null) {
            $this->errors[] = self::EWE_MISSING_INPUT;
            return false;
        }

        $foundAnimal = $this->animalRepository->findAnimalByAnimalArray($eweArray);
        if($foundAnimal == null) {
            $this->errors[] = self::EWE_NO_ANIMAL_FOUND;
            return false;
        }

        if($this->validateEweGender) {
            if(!($foundAnimal instanceof Ewe)) {
                $this->errors[] = self::EWE_FOUND_BUT_NOT_EWE;
                return false;
            }
        }
        
        $isOwnedByClient = Validator::isAnimalOfClient($foundAnimal, $this->client);
        if($isOwnedByClient) {
            return true;
        } else {
            $this->errors[] = self::EWE_NOT_OF_CLIENT;
            return false;
        }
    }


    /**
     * @param ArrayCollection $content
     * @return bool
     */
    private function validateNonAnimalValues($content)
    {
        $allNonAnimalValuesAreValid = true;

        $startDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::START_DATE, $content);
        $endDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::END_DATE, $content);
        $ki = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::KI, $content);
        $pmsg = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::PMSG, $content);

        if($startDate === null) {
            $this->errors[] = self::START_DATE_MISSING;
            $allNonAnimalValuesAreValid =  false;
        } else {
            if($startDate > new \DateTime('now')) {
                $this->errors[] = self::START_DATE_IN_FUTURE;
                $allNonAnimalValuesAreValid =  false;
            }
        }

        if($endDate === null) {
            $this->errors[] = self::END_DATE_MISSING;
            $allNonAnimalValuesAreValid =  false;
        } else {
            if($endDate > new \DateTime('now')) {
                $this->errors[] = self::END_DATE_IN_FUTURE;
                $allNonAnimalValuesAreValid =  false;
            }
        }

        if($startDate != null && $endDate != null) {
            if($startDate > $endDate) {
                $this->errors[] = self::START_AFTER_END_DATE;
                $allNonAnimalValuesAreValid =  false;
            }
        }

        if($ki === null) {
            $this->errors[] = self::KI_MISSING;
            $allNonAnimalValuesAreValid =  false;
        }

        if($pmsg === null) {
            $this->errors[] = self::PMSG_MISSING;
            $allNonAnimalValuesAreValid =  false;
        }

        return $allNonAnimalValuesAreValid;
    }


    /**
     * @param Mate $mate
     * @return bool
     */
    private function validateMateIsNotAlreadyOverwritten(Mate $mate)
    {
        if($mate->getIsOverwrittenVersion()) {
            $this->errors[] = self::MATE_OVERWRITTEN;
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
            return Validator::createJsonResponse(self::VALID_MESSAGE, self::VALID_CODE);
        } else {
            return Validator::createJsonResponse(self::ERROR_MESSAGE, self::ERROR_CODE, $this->errors);
        }
    }


    /**
     * Returns Mate if true.
     *
     * @param ObjectManager $manager
     * @param Client $client
     * @param string $messageId
     * @return Mate|boolean
     */
    public static function isNonRevokedMateOfClient(ObjectManager $manager, $client, $messageId)
    {
        /** @var Mate $mate */
        $mate = $manager->getRepository(Mate::class)->findOneByMessageId($messageId);

        //null check
        if(!($mate instanceof Mate) || $messageId == null) { return false; }

        //Revoke check, to prevent data loss by incorrect data
        if($mate->getRequestState() == RequestStateType::REVOKED) { return false; }

        /** @var Location $locationOfMate */
        $locationOfMate = $manager->getRepository(Location::class)->findOneByUbn($mate->getUbn());

        $locationOwnerOfMate = NullChecker::getOwnerOfLocation($locationOfMate);

        if($locationOwnerOfMate instanceof Client && $client instanceof Client) {
            /** @var Client $locationOwnerOfMate */
            if($locationOwnerOfMate->getId() == $client->getId()) {
                return $mate;
            }
        }

        return false;
    }
}