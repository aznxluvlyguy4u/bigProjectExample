<?php

namespace AppBundle\Validation;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\HeaderBag;


class HeaderValidation
{
    const ERROR_CODE = 428;

    /** @var string */
    private $errorMessage;

    /** @var HeaderBag */
    private $headers;

    /** @var string */
    private $ubn;

    /** @var Client */
    private $client;

    /** @var boolean */
    private $isUbnOfClient;

    /** @var boolean */
    private $hasHeaderUbn;

    /** @var ObjectManager */
    private $manager;

    /**
     * HeaderValidation constructor.
     * @param $request
     * @param Client $client
     */
    public function __construct(ObjectManager $manager, $request, Client $client)
    {
        //set input values
        $this->manager = $manager;
        $this->headers = $request->headers;
        $this->client = $client;

        //validate and set possible values
        $this->validate();
    }

    private function validate()
    {
        $this->verifyHeaderHasUbn();

        if(!$this->hasHeaderUbn) {
            $this->errorMessage = "UBN MISSING FROM HEADER";

        } else { //there is a ubn value given
            $this->isUbnOfClient = $this->validateUbnBelongsToClient($this->client, $this->ubn);

            if(!$this->isUbnOfClient) {
                $this->errorMessage = "UBN DOES NOT BELONG TO THIS CLIENT";
            }
        }
    }

    private function verifyHeaderHasUbn()
    {
        if($this->headers->has(Constant::UBN_NAMESPACE)){
            $ubnInput = $this->headers->get(Constant::UBN_NAMESPACE);

            if($ubnInput != null && $ubnInput != "") {
                $this->ubn = $ubnInput;
                $this->hasHeaderUbn = true;

            } else {
                $this->hasHeaderUbn = false;
            }
        } else {
            $this->hasHeaderUbn = false;
        }
    }

    /**
     * @param Client $client
     * @param string $ubn
     * @return bool
     */
    public function validateUbnBelongsToClient(Client $client, $ubn)
    {
        $isUbnOfClient = false;

        foreach($client->getCompanies() as $company) {
            foreach($company->getLocations() as $location) {
                if($location->getUbn() == $ubn){
                    $isUbnOfClient = true;
                }
            }
        }

        return $isUbnOfClient;
    }

    /**
     * Only create this JsonResponse when there actually are errors.
     *
     * @return JsonResponse
     */
    public function createJsonErrorResponse()
    {
        $result = array(
            Constant::CODE_NAMESPACE => HeaderValidation::ERROR_CODE,
            Constant::MESSAGE_NAMESPACE => $this->errorMessage);

        return new JsonResponse($result, UbnValidator::ERROR_CODE);
    }

    public function getLocation()
    {
        return $this->manager->getRepository(Constant::LOCATION_REPOSITORY)->findByUbn($this->ubn);
    }

    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * @return boolean
     */
    public function isIsUbnOfClient()
    {
        return $this->isUbnOfClient;
    }

    /**
     * @return boolean
     */
    public function hasHeaderUbn()
    {
        return $this->hasHeaderUbn;
    }

    public function isInputValid()
    {
        if($this->isUbnOfClient && $this->hasHeaderUbn) {
            return true;
        } else {
            return false;
        }
    }

}