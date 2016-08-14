<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Constant\Constant;
use Doctrine\ORM\EntityManager;
use \Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ArrivalAndImportValidator
 * @package AppBundle\Validation
 */
class ArrivalAndImportValidator
{
    const RESPONSE_INVALID_INPUT_ULN = "ULN FORMAT INCORRECT. ULN MUST CONSIST OF 2 CAPITAL LETTERS FOLLOWED BY 12 NUMBERS";
    const RESPONSE_INVALID_INPUT_PEDIGREE_NUMBER = "PEDIGREE VALUE IS NOT REGISTERED WITH NSFO";

    const EMPTY_INPUT = 'EMPTY ULN AND PEDIGREE NUMBER';

    const ERROR_CODE = 428;
    const OVERALL_ERROR_MESSAGE = 'INPUT IS INVALID';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'INPUT IS VALID';

    /** @var  boolean */
    protected $isValid;

    /** @var array */
    protected $errors;

    /** @var EntityManager */
    protected $em;

    /**
     * ArrivalAndImportValidator constructor.
     * @param ArrayCollection $content
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, $content)
    {
        //Initialize variables
        $this->errors = array();
        $this->isValid = true;

        //Set given values
        $this->em = $em;

        $this->validate($content);
    }

    public function getIsValid() { return $this->isValid; }

    /**
     * @param ArrayCollection $content
     */
    private function validate($content)
    {
        $animalArray = Utils::getNullCheckedArrayCollectionValue(Constant::ANIMAL_NAMESPACE, $content);
        if($animalArray != null) {

            //If uln is given, verify it
            $ulnCountryCode = Utils::getNullCheckedArrayValue(Constant::ULN_COUNTRY_CODE_NAMESPACE, $animalArray);
            $ulnNumber = Utils::getNullCheckedArrayValue(Constant::ULN_NUMBER_NAMESPACE, $animalArray);
            $isUlnGiven = $ulnCountryCode != null && $ulnNumber != null;
            if($isUlnGiven) {
                $this->validateUln($ulnCountryCode.$ulnNumber);
            }

            //If pedigree is given, verify it
            $pedigreeCountryCode = Utils::getNullCheckedArrayValue(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $animalArray);
            $pedigreeNumber = Utils::getNullCheckedArrayValue(Constant::PEDIGREE_NUMBER_NAMESPACE, $animalArray);
            $isPedigreeGiven = $pedigreeCountryCode != null && $pedigreeNumber != null;
            if($isPedigreeGiven) {
                $this->validatePedigree($pedigreeCountryCode, $pedigreeNumber);
            }

            if(!$isPedigreeGiven && !$isUlnGiven) {
                $this->isValid = false;
                $this->errors[] = self::EMPTY_INPUT;
            }
        }
    }

    /**
     * @param string $ulnString
     */
    protected function validateUln($ulnString)
    {
        if(!Utils::verifyUlnFormat($ulnString)){
            $this->isValid = false;
            $this->errors[$ulnString] = self::RESPONSE_INVALID_INPUT_ULN;
        }
    }

    /**
     * @param string $pedigreeCountryCode
     * @param string $pedigreeNumber
     */
    protected function validatePedigree($pedigreeCountryCode, $pedigreeNumber)
    {
        if(!Utils::verifyPedigreeCode($this->em, $pedigreeCountryCode, $pedigreeNumber)) {
            $this->isValid = false;
            $this->errors[$pedigreeCountryCode.$pedigreeNumber] = self::RESPONSE_INVALID_INPUT_PEDIGREE_NUMBER;
        }
    }

    /**
     * @return JsonResponse
     */
    public function createJsonResponse()
    {
        if($this->isValid) {
            $code = self::VALID_CODE;
            $result = array(
                Constant::CODE_NAMESPACE => $code,
                Constant::MESSAGE_NAMESPACE => self::VALID_MESSAGE,
                Constant::ERRORS_NAMESPACE => $this->errors); //returning an empty array

        } else {
            $code = self::ERROR_CODE;
            $result = array(
                Constant::CODE_NAMESPACE => $code,
                Constant::MESSAGE_NAMESPACE => self::OVERALL_ERROR_MESSAGE,
                Constant::ERRORS_NAMESPACE => $this->errors);
        }

        return new JsonResponse($result, $code);
    }

}