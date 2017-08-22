<?php

namespace AppBundle\Validation;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RamRepository;
use AppBundle\Util\NullChecker;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientInputValidator extends BaseValidator
{
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

    const MAX_EWES_COUNT = 20; // -1 = no limit, also update error message when updating max count
    const EWES_COUNT_EXCEEDS_MAX = 'THE AMOUNT OF SELECTED EWES EXCEEDED 20';

    /** @var AnimalRepository */
    protected $animalRepository;

    /** @var EweRepository */
    protected $eweRepository;

    /** @var RamRepository */
    protected $ramRepository;

    /** @var Client */
    protected $client;
    
    /** @var boolean */
    private $validateEweGender;
    /** @var boolean */
    private $isAdmin;

    public function __construct(ObjectManager $manager, ArrayCollection $content, Client $client = null, $isAdmin = false, $validateEweGender = true)
    {
        parent::__construct($manager, $content);
        $this->animalRepository = $this->manager->getRepository(Animal::class);
        $this->eweRepository = $this->manager->getRepository(Ewe::class);
        $this->ramRepository = $this->manager->getRepository(Ram::class);
        $this->client = $client;
        $this->isAdmin = $isAdmin;
        $this->validateEweGender = $validateEweGender;

        $this->validatePost($content);
    }

    /**
     * @param ArrayCollection $content
     */
    private function validatePost($content) {

        $ewesArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWES, $content);
        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);
        
        $isRamInputValid = $this->validateRamArray($ramArray);

        $isEwesCountValid = true;
        if(self::MAX_EWES_COUNT > 0 && count($ewesArray) > self::MAX_EWES_COUNT) {
            $this->errors[] = self::EWES_COUNT_EXCEEDS_MAX;
            $isEwesCountValid = false;

            $this->isInputValid = false;
            return;
        }

        $isEwesInputValid = true;
        foreach ($ewesArray as $eweArray) {
            $isEweInputValid = $this->validateEweArray($eweArray);
            if(!$isEweInputValid) {
                $isEwesInputValid = false;
            }
        }


        if($isRamInputValid && $isEwesInputValid && $isEwesCountValid) {
            $this->isInputValid = true;
        } else {
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
            $animal = $this->ramRepository->findRamByUlnString($ulnString);
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
                $animal = $this->ramRepository->getRamByArray($ramArray);
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

        $foundAnimal = $this->eweRepository->getEweByArray($eweArray);
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


        //Check ownership

        if ($this->isAdmin) {
            return true;
        }

        $isOwnedByClient = Validator::isAnimalOfClient($foundAnimal, $this->client);
        if($isOwnedByClient) {
            return true;

        } else {
            $this->errors[] = self::EWE_NOT_OF_CLIENT;
            return false;
        }
    }



}