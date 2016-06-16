<?php

namespace AppBundle\Validation;


use AppBundle\Constant\Constant;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;

class UbnValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'ANIMAL WITH GIVEN ULN DOES NOT BELONG TO GIVEN UBN PREVIOUS OWNER';

    /** @var boolean */
    private $isUbnValid;

    /** @var string */
    private $ulnCountryCode;

    /** @var string */
    private $ulnNumber;

    /** @var string */
    private $ubnPreviousOwner;

    /** @var string */
    private $ubnAnimal;

    /** @var ObjectManager */
    private $manager;

    /**
     * UbnValidator constructor.
     * @param ObjectManager $manager
     * @param Collection $content
     */
    public function __construct(ObjectManager $manager, Collection $content)
    {
        $this->manager = $manager;

        //If content is for a DeclareArrival
        if($this->verifyIsDeclareArrival($content)) {
            $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
            $this->ubnPreviousOwner = $content->get(Constant::UBN_PREVIOUS_OWNER_NAMESPACE);
            $this->ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
            $this->ulnNumber = $animalArray[Constant::ULN_NUMBER_NAMESPACE];

            //Validate password
            $this->validateArrivalInput();
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
     * @return bool
     */
    private function verifyIsDeclareArrival(Collection $content)
    {
        if($content->containsKey(Constant::IS_IMPORT_ANIMAL) && $content->containsKey(Constant::UBN_PREVIOUS_OWNER_NAMESPACE)) {
            $ubnPreviousOwner = $content->get(Constant::UBN_PREVIOUS_OWNER_NAMESPACE);
            $isImportAnimal = $content->get(Constant::IS_IMPORT_ANIMAL);
            if($isImportAnimal == false && $ubnPreviousOwner != null && $ubnPreviousOwner != "") {
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    private function validateArrivalInput()
    {
        //Initialize default validity
        $this->isUbnValid = true;

        $animal = $this->manager->getRepository(Constant::ANIMAL_REPOSITORY)->
        findByUlnCountryCodeAndNumber($this->ulnCountryCode, $this->ulnNumber);

        if($animal != null) {
            $this->ubnAnimal = $animal->getLocation()->getUbn();
        }

        //Validation criteria
        if($animal != null) { //only check the ubn for an animal already in our database
            if($this->ubnAnimal != $this->ubnPreviousOwner) {
                $this->isUbnValid = false;
            }
        }
    }

    /**
     * Only create this JsonResponse when there actually are errors.
     *
     * @return JsonResponse
     */
    public function createArrivalJsonErrorResponse()
    {
        $result = array(
            Constant::CODE_NAMESPACE => UbnValidator::ERROR_CODE,
            Constant::MESSAGE_NAMESPACE => UbnValidator::ERROR_MESSAGE,
            Constant::ULN_NAMESPACE => $this->ulnCountryCode . $this->ulnNumber,
            Constant::UBN_NAMESPACE => $this->ubnAnimal,
            Constant::UBN_PREVIOUS_OWNER_NAMESPACE => $this->ubnPreviousOwner);

        return new JsonResponse($result, UbnValidator::ERROR_CODE);
    }



}