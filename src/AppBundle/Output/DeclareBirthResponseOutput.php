<?php

namespace AppBundle\Output;


use AppBundle\Constant\Constant;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;

class DeclareBirthResponseOutput extends Output
{
    /**
     * @param DeclareBirth $birth
     * @param AnimalRepository $animalRepository
     * @return array
     */
    public static function createHistoryResponse($birth, $animalRepository)
    {
        $lastResponse = $birth->getResponses()->first(); //ArrayCollection -> first() returns the last
        if($lastResponse) {
            $messageNumber = $lastResponse->getMessageNumber();
        } else {
            $messageNumber = null;
        }

        $pedigree = $animalRepository->getPedigreeByUln($birth->getUlnCountryCode(), $birth->getUlnNumber());

        return array(
            "request_id" => $birth->getRequestId(),
            "log_date" => $birth->getLogDate(),
            "uln_country_code" => $birth->getUlnCountryCode(),
            "uln_number" => $birth->getUlnNumber(),
            "pedigree_country_code" => $pedigree[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE],
            "pedigree_number" => $pedigree[Constant::PEDIGREE_NUMBER_NAMESPACE],
            "gender" => $birth->getGender(),
            "date_of_birth" => $birth->getDateOfBirth(),
            "request_state" => $birth->getRequestState(),
            "message_number" => $messageNumber
        );
    }

    /**
     * @param DeclareBirth $birth
     * @param AnimalRepository $animalRepository
     * @return array|null
     */
    public static function createErrorResponse($birth, $animalRepository)
    {
        $lastResponse = $birth->getResponses()->first(); //ArrayCollection -> first() returns the last
        if($lastResponse != false) {
            $errorCode = $lastResponse->getErrorCode();
            $errorMessage = $lastResponse->getErrorMessage();
            $messageNumber = $lastResponse->getMessageNumber();
            $isRemovedByUser = $lastResponse->getIsRemovedByUser();
        } else {
            $errorCode = null;
            $errorMessage = null;
            $messageNumber = null;
            $isRemovedByUser = true;
        }

        if($isRemovedByUser) {
            return null;
        }

        $pedigree = $animalRepository->getPedigreeByUln($birth->getUlnCountryCode(), $birth->getUlnNumber());

        $res = array("request_id" => $birth->getRequestId(),
            "log_date" => $birth->getLogDate(),
            "uln_country_code" => $birth->getUlnCountryCode(),
            "uln_number" => $birth->getUlnNumber(),
            "pedigree_country_code" => $pedigree[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE],
            "pedigree_number" => $pedigree[Constant::PEDIGREE_NUMBER_NAMESPACE],
            "gender" => $birth->getGender(),
            "date_of_birth" => $birth->getDateOfBirth(),
            "request_state" => $birth->getRequestState(),
            "error_code" => $errorCode,
            "error_message" => $errorMessage,
            "message_number" => $messageNumber
        );

        return $res;
    }


}