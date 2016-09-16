<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareWeight;
use AppBundle\Entity\Person;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\Collection;

class DeclareWeightOutput
{

    /**
     * @param Collection $declareWeights
     * @return array
     */
    public static function createDeclareWeightsOverview($declareWeights)
    {
        $declareWeightsOutput = array();

        foreach($declareWeights as $declareWeight) {
            $declareWeightsOutput[] = self::createDeclareWeightOverview($declareWeight);
        }

        return $declareWeightsOutput;
    }

    /**
     * @param DeclareWeight $declareWeight
     *
     * @return array
     */
    public static function createDeclareWeightOverview($declareWeight)
    {
        $nullReplacementText = '';

        if($declareWeight->getAnimal() instanceof Animal) {
            $eweUlnCountryCode = $declareWeight->getAnimal()->getUlnCountryCode();
            $eweUlnNumber = $declareWeight->getAnimal()->getUlnNumber();
        } else {
            $eweUlnCountryCode = $nullReplacementText;
            $eweUlnNumber = $nullReplacementText;
        }

        $res = [
            JsonInputConstant::MESSAGE_ID => $declareWeight->getMessageId(),
            JsonInputConstant::MEASUREMENT_DATE => $declareWeight->getMeasurementDate(),
            JsonInputConstant::LOG_DATE => $declareWeight->getLogDate(),
            JsonInputConstant::WEIGHT => $declareWeight->getWeight(),
            JsonInputConstant::IS_BIRTH_WEIGHT => $declareWeight->getIsBirthWeight(),
            JsonInputConstant::ANIMAL => [
                JsonInputConstant::ULN_COUNTRY_CODE => $eweUlnCountryCode,
                JsonInputConstant::ULN_NUMBER => $eweUlnNumber
            ],
            JsonInputConstant::RELATION_NUMBER_KEEPER => $declareWeight->getRelationNumberKeeper(),
            JsonInputConstant::UBN => $declareWeight->getUbn(),
            JsonInputConstant::REQUEST_STATE => $declareWeight->getRequestState(),
            JsonInputConstant::IS_HIDDEN => $declareWeight->getIsHidden(),
            JsonInputConstant::IS_OVERWRITTEN => $declareWeight->getIsOverwrittenVersion(),
            JsonInputConstant::REVOKED_BY => NullChecker::getRevokerPersonId($declareWeight, $nullReplacementText),
            JsonInputConstant::REVOKE_DATE => Utils::fillNullOrEmptyString($declareWeight->getRevokeDate(),$nullReplacementText)
        ];

        return $res;
    }

}
