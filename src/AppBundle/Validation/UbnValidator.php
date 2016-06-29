<?php

namespace AppBundle\Validation;


use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Util\AnimalArrayReader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;

class UbnValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'ANIMAL IS NOT REGISTERED WITH GIVEN UBN PREVIOUS OWNER';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'UBN OF ANIMAL IS VALID';
    const MISSING_CODE = 428;
    const MISSING_MESSAGE = 'NO ULN OR PEDIGREE GIVEN';

    /** @var boolean */
    private $isUbnValid;

    /** @var boolean */
    private $isArrivedFromOtherNsfoClient;

    /** @var string */
    private $identification;

    /** @var string */
    private $ulnCountryCode;

    /** @var string */
    private $ulnNumber;

    /** @var string */
    private $pedigreeCountryCode;

    /** @var string */
    private $pedigreeNumber;

    /** @var string */
    private $ubnPreviousOwner;

    /** @var string */
    private $ubnOriginAnimal;

    /** @var ObjectManager */
    private $manager;

    /**
     * UbnValidator constructor.
     * @param ObjectManager $manager
     * @param Collection $content
     * @param DeclareArrival|null $declareArrival
     */
    public function __construct(ObjectManager $manager, Collection $content, $declareArrival = null)
    {
        $this->manager = $manager;

        //If input is for a DeclareArrival POST
        if($this->verifyIsDeclareArrivalPost($content, $declareArrival)) {
            $this->ubnPreviousOwner = $content->get(JsonInputConstant::UBN_PREVIOUS_OWNER);

            $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
            $animalIdentification = AnimalArrayReader::readUlnOrPedigree($animalArray);
            $this->identification = $animalIdentification[Constant::TYPE_NAMESPACE];
            if($this->identification == Constant::ULN_NAMESPACE) {
                $this->ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
                $this->ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];
            } else if ($this->identification == Constant::PEDIGREE_NAMESPACE) {
                $this->pedigreeCountryCode = $animalArray[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE];
                $this->pedigreeNumber = $animalArray[Constant::PEDIGREE_NUMBER_NAMESPACE];
            }

            //Validate ubn values
            $this->validateArrivalPostInput();
        }

        //If input is for a DeclareArrival PUT
        if($this->verifyIsDeclareArrivalPut($content, $declareArrival)) {
            $animal = $declareArrival->getAnimal();

            $this->identification = Constant::ULN_NAMESPACE;
            $this->ulnCountryCode = $animal->getUlnCountryCode();
            $this->ulnNumber = $animal->getUlnNumber();
            $this->ubnOriginAnimal = $declareArrival->getUbnPreviousOwner();

            $this->ubnPreviousOwner = $content->get(JsonInputConstant::UBN_PREVIOUS_OWNER);
            $this->isArrivedFromOtherNsfoClient = $declareArrival->getIsArrivedFromOtherNsfoClient();

            //Validate ubn values
            $this->validateArrivalPutInput();
        }
    }

    /**
     * @return bool
     */
    public function getIsUbnValid() {
        return $this->isUbnValid;
    }

    /**
     * @param Collection $content
     * @param DeclareArrival|null $declareArrival
     * @return bool
     */
    private function verifyIsDeclareArrivalPost(Collection $content, $declareArrival)
    {
        if($content->containsKey(JsonInputConstant::IS_IMPORT_ANIMAL)
            && $content->containsKey(JsonInputConstant::UBN_PREVIOUS_OWNER)
            && $declareArrival == null
           ) {
            $ubnPreviousOwner = $content->get(JsonInputConstant::UBN_PREVIOUS_OWNER);
            $isImportAnimal = $content->get(JsonInputConstant::IS_IMPORT_ANIMAL);
            if($isImportAnimal == false && $ubnPreviousOwner != null && $ubnPreviousOwner != "") {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Collection $content
     * @param DeclareArrival|null $declareArrival
     * @return bool
     */
    private function verifyIsDeclareArrivalPut(Collection $content, $declareArrival)
    {
        if($content->containsKey(JsonInputConstant::REQUEST_ID)
            && $content->containsKey(JsonInputConstant::UBN_PREVIOUS_OWNER)
            && $content->containsKey(JsonInputConstant::ARRIVAL_DATE)
            && $declareArrival != null)
        {
            $requestId = $content->get(JsonInputConstant::REQUEST_ID);
            if($requestId != null && $requestId != "") {
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    private function validateArrivalPostInput()
    {
        $animal = null;

        if($this->identification == Constant::ULN_NAMESPACE) {
            $animal = $this->manager->getRepository(Constant::ANIMAL_REPOSITORY)->
            findByUlnCountryCodeAndNumber($this->ulnCountryCode, $this->ulnNumber);
        } else if ($this->identification == Constant::PEDIGREE_NAMESPACE) {
            $animal = $this->manager->getRepository(Constant::ANIMAL_REPOSITORY)->
            findByPedigreeCountryCodeAndNumber($this->pedigreeCountryCode, $this->pedigreeNumber);
        }

        if($animal != null) {
            $this->ubnOriginAnimal = $animal->getLocation()->getUbn();
            $this->ulnCountryCode = $animal->getUlnCountryCode();
            $this->ulnNumber = $animal->getUlnNumber();
            $this->pedigreeCountryCode = $animal->getPedigreeCountryCode();
            $this->pedigreeNumber = $animal->getPedigreeNumber();
            $this->isArrivedFromOtherNsfoClient = true;
        } else {
            $this->isArrivedFromOtherNsfoClient = false;
        }

        //Validation criteria
        if($animal != null) { //only check the ubn for an animal already in our database
            $this->validateUbnPreviousOwner();
        } else {
            $this->isUbnValid = true;
        }
    }

    private function validateArrivalPutInput()
    {
        if($this->isArrivedFromOtherNsfoClient == true) {
            $this->validateUbnPreviousOwner();
        } else {
            $this->isUbnValid = true;
        }
    }

    /**
     *
     */
    private function validateUbnPreviousOwner()
    {
        //Initialize default validity
        $this->isUbnValid = true;

        if($this->ubnOriginAnimal != $this->ubnPreviousOwner) {
            $this->isUbnValid = false;
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
        $pedigree = null;

        if($this->isUbnValid) {
            $code = UbnValidator::VALID_CODE;
            $message = UbnValidator::VALID_MESSAGE;
        } else {
            $code = UbnValidator::ERROR_CODE;
            $message = UbnValidator::ERROR_MESSAGE;
        }

        //Only return the values for the identification type being tested
        if($this->identification == Constant::ULN_NAMESPACE) {
            $uln = $this->ulnCountryCode . $this->ulnNumber;
        } else if ($this->identification == Constant::PEDIGREE_NAMESPACE) {
            $pedigree = $this->pedigreeCountryCode . $this->pedigreeNumber;

        } else { //If no ULN or PEDIGREE found
            $code = UbnValidator::MISSING_CODE;
            $message = UbnValidator::MISSING_MESSAGE;
        }

        $result = array(
            Constant::CODE_NAMESPACE => $code,
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::TYPE_NAMESPACE => $this->identification,
            Constant::ULN_NAMESPACE => $uln,
            Constant::PEDIGREE_NAMESPACE => $pedigree,
            Constant::UBN_NAMESPACE => $this->ubnOriginAnimal,
            Constant::UBN_PREVIOUS_OWNER_NAMESPACE => $this->ubnPreviousOwner);

        return new JsonResponse($result, UbnValidator::ERROR_CODE);
    }

    /**
     * @return boolean
     */
    public function getIsArrivedFromOtherNsfoClient()
    {
        return $this->isArrivedFromOtherNsfoClient;
    }

}