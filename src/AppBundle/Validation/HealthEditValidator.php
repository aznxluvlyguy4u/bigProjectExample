<?php

namespace AppBundle\Validation;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\MaediVisnaStatus;
use AppBundle\Enumerator\ScrapieStatus;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class HealthEditValidator
 * @package AppBundle\Validation
 */
class HealthEditValidator
{
    const RESPONSE_EMPTY_INPUT_MAEDI_VISNA_STATUS = "MAEDI VISNA STATUS CANNOT BE EMPTY";
    const RESPONSE_INVALID_INPUT_MAEDI_VISNA_STATUS = "MAEDI VISNA STATUS WAS NOT FOUND IN THE ACCEPTED STATUS LIST";
    const RESPONSE_EMPTY_INPUT_SCRAPIE_STATUS = "SCRAPIE STATUS CANNOT BE EMPTY";
    const RESPONSE_INVALID_INPUT_SCRAPIE_STATUS = "SCRAPIE STATUS WAS NOT FOUND IN THE ACCEPTED STATUS LIST";
    const RESPONSE_INVALID_INPUT_MAEDI_VISNA_CHECK_DATE = "MAEDI VISNA CHECK DATE CANNOT BE IN THE PAST";
    const RESPONSE_INVALID_INPUT_SCRAPIE_CHECK_DATE = "SCRAPIE CHECK DATE CANNOT BE IN THE FUTURE";

    const EMPTY_MAEDI_VISNA_STATUS = 'EMPTY MAEDI VISNA STATUS';
    const EMPTY_SCRAPIE_STATUS = 'EMPTY SCRAPIE STATUS';
    const INVALID_MAEDI_VISNA_STATUS = 'INVALID MAEDI VISNA STATUS';
    const INVALID_SCRAPIE_STATUS = 'INVALID SCRAPIE STATUS';

    const ERROR_CODE = 428;
    const OVERALL_ERROR_MESSAGE = 'INPUT IS INVALID';
    const VALID_CODE = 200;
    const VALID_MESSAGE = 'INPUT IS VALID';

    /** @var  boolean */
    private $isValid;

    /** @var  \DateTime */
    private $today;

    /** @var array */
    private $errors;

    /** @var ObjectManager */
    private $em;

    /**
     * PasswordValidator constructor.
     * @param ArrayCollection $content
     */
    public function __construct(ObjectManager $em, $content)
    {
        //Initialize variables
        $this->errors = array();
        $this->isValid = true;

        $this->today = new \DateTime('today');

        //Set given values
        $this->em = $em;

        //Validate
        $this->validate($content);

    }

    public function getIsValid() { return $this->isValid; }

    /**
     * @param ArrayCollection $content
     */
    private function validate($content)
    {
        $scrapieStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_STATUS, $content);
        $maediVisnaStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_STATUS, $content);

        $scrapieCheckDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::SCRAPIE_CHECK_DATE, $content, true);
        $maediVisnaCheckDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::MAEDI_VISNA_CHECK_DATE, $content, true);

        $this->validateMaediVisnaStatus($maediVisnaStatus);
        $this->validateScrapieStatus($scrapieStatus);

        $this->validateMaediVisnaCheckDate($maediVisnaCheckDate);
        $this->validateScrapieCheckDate($scrapieCheckDate);
    }


    /**
     * @param string $maediVisnaStatus
     */
    private function validateMaediVisnaStatus($maediVisnaStatus)
    {
        $maediVisnaStatus = StringUtil::replaceUnderscoresWithSpaces($maediVisnaStatus);

        if($maediVisnaStatus == null || $maediVisnaStatus == "" || $maediVisnaStatus == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_MAEDI_VISNA_STATUS] = self::RESPONSE_EMPTY_INPUT_MAEDI_VISNA_STATUS;

        } elseif (!in_array($maediVisnaStatus, MaediVisnaStatus::getConstants())) {
            $this->isValid = false;
            $this->errors[self::INVALID_MAEDI_VISNA_STATUS] = self::RESPONSE_INVALID_INPUT_MAEDI_VISNA_STATUS;
        }
    }


    /**
     * @param string $scrapieStatus
     */
    private function validateScrapieStatus($scrapieStatus)
    {
        $scrapieStatus = StringUtil::replaceUnderscoresWithSpaces($scrapieStatus);

        if($scrapieStatus == null || $scrapieStatus == "" || $scrapieStatus == " ") {
            $this->isValid = false;
            $this->errors[self::EMPTY_SCRAPIE_STATUS] = self::RESPONSE_EMPTY_INPUT_SCRAPIE_STATUS;

        } elseif (!in_array($scrapieStatus, ScrapieStatus::getConstants())) {
            $this->isValid = false;
            $this->errors[self::INVALID_SCRAPIE_STATUS] = self::RESPONSE_INVALID_INPUT_SCRAPIE_STATUS;
        }
    }


    /**
     * @param \DateTime|null $maediVisnaCheckDate
     */
    private function validateMaediVisnaCheckDate($maediVisnaCheckDate)
    {
        if($maediVisnaCheckDate != null) {
            if($maediVisnaCheckDate < $this->today) {
                $this->isValid = false;
                $this->errors[JsonInputConstant::MAEDI_VISNA_CHECK_DATE] = self::RESPONSE_INVALID_INPUT_MAEDI_VISNA_CHECK_DATE;
            }
        }
    }


    /**
     * @param \DateTime|null $scrapieCheckDate
     */
    private function validateScrapieCheckDate($scrapieCheckDate)
    {
        if($scrapieCheckDate != null) {
            if($scrapieCheckDate > $this->today) {
                $this->isValid = false;
                $this->errors[JsonInputConstant::SCRAPIE_CHECK_DATE] = self::RESPONSE_INVALID_INPUT_SCRAPIE_CHECK_DATE;
            }
        }
    }


    /**
     * @return JsonResponse|\AppBundle\Component\HttpFoundation\JsonResponse
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