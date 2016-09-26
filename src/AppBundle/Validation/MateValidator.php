<?php

namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class MateValidator extends DeclareNsfoBaseValidator
{
    const IS_VALIDATE_IF_START_DATE_IS_IN_THE_FUTURE = false;
    const IS_VALIDATE_IF_END_DATE_IS_IN_THE_FUTURE = false;

    const RAM_MISSING_INPUT               = 'STUD RAM: NO ULN OR PEDIGREE GIVEN';
    const RAM_ULN_FORMAT_INCORRECT        = 'STUD RAM: ULN FORMAT INCORRECT';
    const RAM_ULN_FOUND_BUT_NOT_MALE      = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN ULN IS NOT MALE';
    const RAM_PEDIGREE_FOUND_BUT_NOT_MALE = 'STUD RAM: ANIMAL FOUND IN DATABASE WITH GIVEN PEDIGREE IS NOT MALE';
    const RAM_PEDIGREE_NOT_FOUND          = 'STUD RAM: NO ANIMAL FOUND FOR GIVEN PEDIGREE';
    const RAM_ULN_NOT_FOUND               = 'STUD RAM: NO ANIMAL FOUND FOR GIVEN ULN';

    const EWE_MISSING_INPUT     = 'STUD EWE: NO ULN GIVEN';
    const EWE_NO_ANIMAL_FOUND   = 'STUD EWE: NO ANIMAL FOUND FOR GIVEN ULN';
    const EWE_FOUND_BUT_NOT_EWE = 'STUD EWE: ANIMAL WAS FOUND FOR GIVEN ULN, BUT WAS NOT AN EWE ENTITY';
    const EWE_NOT_OF_CLIENT     = 'STUD EWE: FOUND EWE DOES NOT BELONG TO CLIENT';

    const START_DATE_MISSING               = 'START DATE MISSING';
    const END_DATE_MISSING                 = 'END DATE MISSING';
    const START_DATE_IN_FUTURE             = 'START DATE CANNOT BE IN THE FUTURE';
    const END_DATE_IN_FUTURE               = 'END DATE CANNOT BE IN THE FUTURE';
    const START_AFTER_END_DATE             = 'START DATE CANNOT BE AFTER END DATE';
    const START_DATE_IS_IN_A_MATING_PERIOD = 'THE START DATE OVERLAPS A REGISTERED MATING PERIOD';
    const END_DATE_IS_IN_A_MATING_PERIOD   = 'THE END DATE OVERLAPS A REGISTERED MATING PERIOD';

    const KI_MISSING         = 'KI MISSING';
    const PMSG_MISSING       = 'PMSG MISSING';

    /** @var boolean */
    private $validateEweGender;

    /** @var Mate */
    private $mate;

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client, $validateEweGender = true, $isPost = true)
    {
        parent::__construct($manager, $content, $client);
        $this->validateEweGender = $validateEweGender;

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

        $isNonAnimalInputValid = $this->validateNonAnimalValues($content);
        $isRamInputValid = $this->validateRamArray($ramArray);
        $isEweInputValid = $this->validateEweArray($eweArray, $content);


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

        $foundMate = $this->isNonRevokedNsfoDeclarationOfClient($messageId);
        if(!($foundMate instanceof Mate)) {
            $this->errors[] = self::MESSAGE_ID_ERROR;
            $isMessageIdValid = false;
        } else {
            $this->mate = $foundMate;
            $isMessageIdValid = true;

            $isNotOverwritten = $this->validateNsfoDeclarationIsNotAlreadyOverwritten($foundMate);
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
            if(!$animal) {
                $this->errors[] = self::RAM_ULN_NOT_FOUND;
                return false;
            }

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
    private function validateEweArray($eweArray, $content) {

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

            /** @var DeclareBirth $birth */
            foreach($foundAnimal->getBirths() as $birth) {
                $startDate = new \DateTime($content['start_date']);
                $startDate = $startDate->setTime(0,0,0);

                $birthDate = $birth->getDateOfBirth();
                $interval = $startDate->diff($birthDate);

                if($interval->days < 42) {
                    $this->errors[] = "THE LAST BIRTH AND THE CURRENT MATE PERIOD ARE LESS THAN 6 WEEKS";
                    return false;
                }

            }

            /** @var Mate $mate */
            foreach($foundAnimal->getMatings() as $mate) {
                if($mate->getRequestState() != "REVOKED") {
                    $startDate = new \DateTime($content['start_date']);
                    $startDate = $startDate->setTime(0,0,0);

                    $endDate = new \DateTime($content['end_date']);
                    $endDate = $endDate->setTime(0,0,0);

                    $mateStartDate = $mate->getStartDate();
                    $mateStartDate = $mateStartDate->setTime(0,0,0);

                    $mateEndDate = $mate->getEndDate();
                    $mateEndDate = $mateEndDate->setTime(0,0,0);

                    if($startDate >= $mateStartDate && $startDate <= $mateEndDate) {
                        $this->errors[] = self::START_DATE_IS_IN_A_MATING_PERIOD;
                        return false;
                    }

                    if($endDate >= $mateStartDate && $endDate <= $mateEndDate) {
                        $this->errors[] = self::END_DATE_IS_IN_A_MATING_PERIOD;
                        return false;
                    }
                }
            }

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
        }
        else {
            if(self::IS_VALIDATE_IF_START_DATE_IS_IN_THE_FUTURE) {
                if($startDate > new \DateTime('now')) {
                    $this->errors[] = self::START_DATE_IN_FUTURE;
                    $allNonAnimalValuesAreValid =  false;
                }
            }
        }

        if($endDate === null) {
            $this->errors[] = self::END_DATE_MISSING;
            $allNonAnimalValuesAreValid =  false;
        }
        else {
            if(self::IS_VALIDATE_IF_END_DATE_IS_IN_THE_FUTURE) {
                if ($endDate > new \DateTime('now')) {
                    $this->errors[] = self::END_DATE_IN_FUTURE;
                    $allNonAnimalValuesAreValid = false;
                }
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



}