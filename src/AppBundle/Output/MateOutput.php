<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Ram;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Collections\Collection;

class MateOutput
{

    /**
     * @param Collection $matings
     * @return array
     */
    public static function createMatesOverview($matings)
    {
        $matingsOutput = array();

        foreach($matings as $mate) {
            $matingsOutput[] = self::createMateOverview($mate);
        }

        return $matingsOutput;
    }

    /**
     * @param Mate $mate
     *
     * @return array
     */
    public static function createMateOverview($mate)
    {
        $nullReplacementText = '';

        if($mate->getStudEwe() instanceof Ewe) {
            /** @var Ewe $studEwe */
            $studEwe = $mate->getStudEwe();
            $eweUlnCountryCode = $studEwe->getUlnCountryCode();
            $eweUlnNumber = $studEwe->getUlnNumber();
            $eweCollarColor = $studEwe->getCollarColor();
            $eweCollarNumber = $studEwe->getCollarNumber();
        } else {
            $eweUlnCountryCode = $nullReplacementText;
            $eweUlnNumber = $nullReplacementText;
            $eweCollarColor = $nullReplacementText;
            $eweCollarNumber = $nullReplacementText;
        }

       if ($mate->getStudRam() instanceof Ram) {
           /** @var Ram $studRam */
           $studRam = $mate->getStudRam();
           $ramCollarColor = $studRam->getCollarColor();
           $ramCollarNumber = $studRam->getCollarNumber();
       } else {
           $ramCollarColor = $nullReplacementText;
           $ramCollarNumber = $nullReplacementText;
       }

        $res = [
            JsonInputConstant::MESSAGE_ID => $mate->getMessageId(),
            JsonInputConstant::START_DATE => $mate->getStartDate(),
            JsonInputConstant::END_DATE => $mate->getEndDate(),
            JsonInputConstant::LOG_DATE => $mate->getLogDate(),
            JsonInputConstant::KI => $mate->getKi(),
            JsonInputConstant::PMSG => $mate->getPmsg(),
            JsonInputConstant::RAM => [
                JsonInputConstant::ULN_COUNTRY_CODE => $mate->getRamUlnCountryCode(),
                JsonInputConstant::ULN_NUMBER => $mate->getRamUlnNumber(),
                JsonInputConstant::COLLAR_COLOR => $ramCollarColor,
                JsonInputConstant::COLLAR_NUMBER => $ramCollarNumber
            ],
            JsonInputConstant::EWE => [
                JsonInputConstant::ULN_COUNTRY_CODE => $eweUlnCountryCode,
                JsonInputConstant::ULN_NUMBER => $eweUlnNumber,
                JsonInputConstant::COLLAR_COLOR => $eweCollarColor,
                JsonInputConstant::COLLAR_NUMBER => $eweCollarNumber
            ],
            JsonInputConstant::RELATION_NUMBER_KEEPER => $mate->getRelationNumberKeeper(),
            JsonInputConstant::UBN => $mate->getUbn(),
            JsonInputConstant::REQUEST_STATE => $mate->getRequestState(),
            JsonInputConstant::IS_HIDDEN => $mate->getIsHidden(),
            JsonInputConstant::IS_OVERWRITTEN => $mate->getIsOverwrittenVersion(),
            JsonInputConstant::REVOKED_BY => NullChecker::getRevokerPersonId($mate, $nullReplacementText),
            JsonInputConstant::REVOKE_DATE => Utils::fillNullOrEmptyString($mate->getRevokeDate(),$nullReplacementText)
        ];

        return $res;
    }



    /**
     * @param Collection $matings
     * @return array
     */
    public static function createMatesStudRamsOverview($matings)
    {
        $matingsOutput = array();

        foreach($matings as $mate) {
            $matingsOutput[] = self::createStudRamMateOverview($mate);
        }

        return $matingsOutput;
    }
    
    
    /**
     * @param Mate $mate
     * @return array
     */
    public static function createStudRamMateOverview(Mate $mate)
    {
        $nullReplacementText = '-';

        $ewe = $mate->getStudEwe();
        $ram = $mate->getStudRam();

        if($ewe instanceof Ewe) {
            $eweOutput = AnimalOutput::createAnimalArrayWithoutWeight($ewe);
        } else {
            $eweOutput = $nullReplacementText;
        }

        if($ram instanceof Ram) {
            $ramOutput = AnimalOutput::createAnimalArrayWithoutWeight($ewe);
        } else {
            $ramOutput = $nullReplacementText;
        }

        $res = [
            JsonInputConstant::MESSAGE_ID => $mate->getMessageId(),
            JsonInputConstant::START_DATE => $mate->getStartDate(),
            JsonInputConstant::END_DATE => $mate->getEndDate(),
            JsonInputConstant::LOG_DATE => $mate->getLogDate(),
            JsonInputConstant::KI => $mate->getKi(),
            JsonInputConstant::PMSG => $mate->getPmsg(),
            JsonInputConstant::RAM => $ramOutput,
            JsonInputConstant::EWE => $eweOutput,
            JsonInputConstant::RELATION_NUMBER_KEEPER => $mate->getRelationNumberKeeper(),
            JsonInputConstant::UBN => $mate->getUbn(),
            JsonInputConstant::REQUEST_STATE => $mate->getRequestState(),
            JsonInputConstant::IS_HIDDEN => $mate->getIsHidden(),
            JsonInputConstant::IS_OVERWRITTEN => $mate->getIsOverwrittenVersion()
        ];

        return $res;
    }
}
