<?php

namespace AppBundle\Validation;


use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareImport;
use AppBundle\Util\AnimalArrayReader;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;

class TagValidator
{
    const ERROR_CODE = 428;
    const ERROR_MESSAGE = 'ULN NUMBER DOES NOT BELONG TO A VALID UNASSIGNED TAG';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'ULN NUMBER OF TAG IS VALID';
    const MISSING_CODE = 428;
    const MISSING_MESSAGE = 'NO ULN NUMBER OF TAG GIVEN';
    const EMPTY_CODE = 428;
    const EMPTY_MESSAGE = 'TAG COLLECTION IS EMPTY. HAVE THE TAGS ALREADY BEEN SYNCED?';

    /** @var boolean */
    private $isTagValid;

    /** @var boolean */
    private $isInputEmpty;

    /** @var boolean */
    private $isTagCollectionEmpty;

    /** @var string */
    private $ulnCountryCode;

    /** @var string */
    private $ulnNumberTag;

    /** @var Client */
    private $client;

    /** @var ObjectManager */
    private $manager;

    /**
     * TagValidator constructor.
     * @param ObjectManager $manager
     * @param Collection $content
     * @param DeclareImport|null $declareImport
     */
    public function __construct(ObjectManager $manager, Client $client, Collection $content, $declareImport = null)
    {
        $this->manager = $manager;
        $this->client = $client;

        //If input is for a DeclareImport POST
        if($this->verifyIsDeclareImportPost($content, $declareImport)) {

            $animalArray = $content->get(Constant::ANIMAL_NAMESPACE);
            $this->ulnCountryCode = $animalArray[Constant::ULN_COUNTRY_CODE_NAMESPACE];
            $this->ulnNumberTag = $animalArray[Constant::ULN_NUMBER_NAMESPACE];

            //Validate tag values
            $this->validateImportPostInput();
        }

        //If input is for a DeclareImport PUT
        if($this->verifyIsDeclareImportPut($content, $declareImport)) {
            //no validation
        }
    }

    /**
     * @return bool
     */
    public function getIsTagValid() {
        return $this->isTagValid;
    }

    /**
     * @return boolean
     */
    public function getIsTagCollectionEmpty()
    {
        return $this->isTagCollectionEmpty;
    }

    /**
     * @return boolean
     */
    public function getIsInputEmpty()
    {
        return $this->isInputEmpty;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return !$this->getIsTagCollectionEmpty() && $this->getIsTagValid() && !$this->getIsInputEmpty();
    }


    /**
     * @param Collection $content
     * @param DeclareImport|null $declareImport
     * @return bool
     */
    private function verifyIsDeclareImportPost(Collection $content, $declareImport)
    {
        if($content->containsKey(JsonInputConstant::IS_IMPORT_ANIMAL)
            && $content->containsKey(JsonInputConstant::COUNTRY_ORIGIN)
            && $declareImport == null
           ) {
            $countryOrigin = $content->get(JsonInputConstant::COUNTRY_ORIGIN);
            $isImportAnimal = $content->get(JsonInputConstant::IS_IMPORT_ANIMAL);
            if($isImportAnimal == true && $countryOrigin != null && $countryOrigin != "") {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Collection $content
     * @param DeclareImport|null $declareImport
     * @return bool
     */
    private function verifyIsDeclareImportPut(Collection $content, $declareImport)
    {
        if($content->containsKey(JsonInputConstant::REQUEST_ID)
            && $content->containsKey(JsonInputConstant::COUNTRY_ORIGIN)
            && $content->containsKey(JsonInputConstant::ARRIVAL_DATE)
            && $declareImport != null)
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
    private function validateImportPostInput()
    {
        $repository = $this->manager->getRepository(Constant::TAG_REPOSITORY);
        $tagCountOfClient = $repository->findTags($this->client)->count();

        if($tagCountOfClient > 0) {
            $this->isTagCollectionEmpty = false;
        } else {
            $this->isTagCollectionEmpty = true;
        }

        if($this->ulnNumberTag == null || $this->ulnNumberTag == "") {
            $this->isInputEmpty = true;
        } else {
            $this->isInputEmpty = false;

            $isAnUnassignedTag = $repository->isAnUnassignedTag($this->client, $this->ulnNumberTag);

            if($isAnUnassignedTag) {
                $this->isTagValid = true;
            } else {
                $this->isTagValid = false;
            }
        }
    }

    /**
     * Only create this JsonResponse when there actually are errors.
     *
     * @return JsonResponse
     */
    public function createImportJsonErrorResponse()
    {
        if($this->isTagCollectionEmpty) {
            $code = TagValidator::EMPTY_CODE;
            $message = TagValidator::EMPTY_MESSAGE;

        } else {
            if($this->isInputEmpty) {
                $code = TagValidator::MISSING_CODE;
                $message = TagValidator::MISSING_MESSAGE;

            } else {
                if($this->isTagValid) {
                    $code = TagValidator::VALID_CODE;
                    $message = TagValidator::VALID_MESSAGE;

                } else {
                    $code = TagValidator::ERROR_CODE;
                    $message = TagValidator::ERROR_MESSAGE;
                }
            }
        }

        $result = array(
            Constant::CODE_NAMESPACE => $code,
            Constant::MESSAGE_NAMESPACE => $message,
            Constant::ULN_NAMESPACE => $this->ulnCountryCode . $this->ulnNumberTag);

        return new JsonResponse($result, $code);
    }



}