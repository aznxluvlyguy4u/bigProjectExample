<?php

namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Person;
use AppBundle\Service\ControllerServiceBase;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Translation\TranslatorInterface;

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
    const LAST_BIRTH_AND_CURRENT_MATE_LESS_THAN_6_WEEKS = 'THE LAST BIRTH AND THE CURRENT MATE PERIOD ARE LESS THAN 6 WEEKS';

    /** @var boolean */
    private $validateEweGender;

    /** @var Mate */
    private $mate;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(ObjectManager $manager, TranslatorInterface $translator,
                                ArrayCollection $content, Client $client, Person $loggedInUser,
                                $validateEweGender = true, $isPost = true)
    {
        parent::__construct($manager, $content, $client, $loggedInUser);
        $this->validateEweGender = $validateEweGender;
        $this->translator = $translator;

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
     * @param string $string
     * @return string
     */
    private function trans($string)
    {
        return ControllerServiceBase::translateWithUcFirstLower($this->translator, $string);
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
            $this->errors[] =  $this->trans(self::MESSAGE_ID_ERROR);
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
        $isEweInputValid = $this->validateEweArray($eweArray, $content, $messageId);
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
            $this->errors[] = $this->trans(self::RAM_MISSING_INPUT);
            return false;
        }

        //Then validate the uln if it exists
        $ulnString = NullChecker::getUlnStringFromArray($ramArray, null);
        if ($ulnString != null) {
            //ULN check

            $isUlnFormatValid = Validator::verifyUlnFormat($ulnString);
            if(!$isUlnFormatValid) {
                $this->errors[] = $this->trans(self::RAM_ULN_FORMAT_INCORRECT);
                return false;
            }

            //If animal is in database, verify the gender
            $animal = $this->animalRepository->findAnimalByUlnString($ulnString);
            if(!$animal) {
                $this->errors[] = $this->trans(self::RAM_ULN_NOT_FOUND);
                return false;
            }

            $isMaleCheck = $this->validateIfRamUlnBelongsToMaleIfFoundInDatabase($animal);

            if(!$isMaleCheck) {
                $this->errors[] = $this->trans(self::RAM_ULN_FOUND_BUT_NOT_MALE);
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
                    $this->errors[] = $this->trans(self::RAM_PEDIGREE_FOUND_BUT_NOT_MALE);
                }
                return $isMaleCheck;
            } else {
                $this->errors[] = $this->trans(self::RAM_PEDIGREE_NOT_FOUND);
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
     * @param ArrayCollection $content
     * @param $mateRequestId
     * @return bool
     */
    private function validateEweArray($eweArray, $content, $mateRequestId = null) {

        $ulnString = NullChecker::getUlnStringFromArray($eweArray, null);
        if($ulnString == null) {
            $this->errors[] = $this->trans(self::EWE_MISSING_INPUT);
            return false;
        }

        $foundAnimal = $this->animalRepository->findAnimalByAnimalArray($eweArray);
        if($foundAnimal == null) {
            $this->errors[] = $this->trans(self::EWE_NO_ANIMAL_FOUND);
            return false;
        }

        if($this->validateEweGender) {
            if(!($foundAnimal instanceof Ewe)) {
                $this->errors[] = $this->trans(self::EWE_FOUND_BUT_NOT_EWE);
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
                    $this->errors[] = $this->trans(self::LAST_BIRTH_AND_CURRENT_MATE_LESS_THAN_6_WEEKS);
                    return false;
                }

            }

            /** @var Mate $mate */
            foreach($foundAnimal->getMatings() as $mate) {

                if(!$mate->getIsOverwrittenVersion() && $mate->getRequestState() != "REVOKED") {

                    if ($mate->getMessageId() !== $mateRequestId) {

                        $startDate = new \DateTime($content['start_date']);
                        $startDate = $startDate->setTime(0,0,0);

                        $endDate = new \DateTime($content['end_date']);
                        $endDate = $endDate->setTime(0,0,0);

                        $mateStartDate = $mate->getStartDate();
                        $mateStartDate = $mateStartDate->setTime(0,0,0);

                        $mateEndDate = $mate->getEndDate();
                        $mateEndDate = $mateEndDate->setTime(0,0,0);

                        if($startDate >= $mateStartDate && $startDate <= $mateEndDate) {
                            $this->errors[] = $this->trans(self::START_DATE_IS_IN_A_MATING_PERIOD);
                            return false;
                        }

                        if($endDate >= $mateStartDate && $endDate <= $mateEndDate) {
                            $this->errors[] = $this->trans(self::END_DATE_IS_IN_A_MATING_PERIOD);
                            return false;
                        }

                    }

                }
            }

            return true;
        } else {
            $this->errors[] = $this->trans(self::EWE_NOT_OF_CLIENT);
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
        $startDate->setTime(0,0,0);
        $endDate->setTime(0,0,0);
        $ki = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::KI, $content);
        $pmsg = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::PMSG, $content);

        if($startDate === null) {
            $this->errors[] = $this->trans(self::START_DATE_MISSING);
            $allNonAnimalValuesAreValid =  false;
        }

        if($startDate > new \DateTime('now')) {
            $this->errors[] = $this->trans(self::START_DATE_IN_FUTURE);
            $allNonAnimalValuesAreValid =  false;
        }

        if($endDate === null) {
            $this->errors[] = $this->trans(self::END_DATE_MISSING);
            $allNonAnimalValuesAreValid =  false;
        }

        if ($endDate > new \DateTime('now')) {
            $this->errors[] = $this->trans(self::END_DATE_IN_FUTURE);
            $allNonAnimalValuesAreValid = false;
        }

        if($startDate != null && $endDate != null) {
            if($startDate > $endDate) {
                $this->errors[] = $this->trans(self::START_AFTER_END_DATE);
                $allNonAnimalValuesAreValid =  false;
            }
        }

        if($ki === null) {
            $this->errors[] = $this->trans(self::KI_MISSING);
            $allNonAnimalValuesAreValid =  false;
        }

        if($pmsg === null) {
            $this->errors[] = $this->trans(self::PMSG_MISSING);
            $allNonAnimalValuesAreValid =  false;
        }

        return $allNonAnimalValuesAreValid;
    }



}
