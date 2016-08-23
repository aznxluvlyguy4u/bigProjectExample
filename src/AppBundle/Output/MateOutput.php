<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Client;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Pedigree;
use AppBundle\Entity\Ram;
use Doctrine\Common\Collections\Collection;
use AppBundle\Component\Count;

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
        $nullReplacementText = '-';

        if($mate->getStudEwe() instanceof Ewe) {
            $eweUlnCountryCode = $mate->getStudEwe()->getUlnCountryCode();
            $eweUlnNumber = $mate->getStudEwe()->getUlnNumber();
        } else {
            $eweUlnCountryCode = $nullReplacementText;
            $eweUlnNumber = $nullReplacementText;
        }

        $res = [
            JsonInputConstant::START_DATE => $mate->getStartDate(),
            JsonInputConstant::END_DATE => $mate->getEndDate(),
            JsonInputConstant::LOG_DATE => $mate->getLogDate(),
            JsonInputConstant::KI => $mate->getKi(),
            JsonInputConstant::PMSG => $mate->getPmsg(),
            JsonInputConstant::RAM => [
                JsonInputConstant::ULN_COUNTRY_CODE => $mate->getRamUlnCountryCode(),
                JsonInputConstant::ULN_NUMBER => $mate->getRamUlnNumber()
            ],
            JsonInputConstant::EWE => [
                JsonInputConstant::ULN_COUNTRY_CODE => $eweUlnCountryCode,
                JsonInputConstant::ULN_NUMBER => $eweUlnNumber
            ],
            JsonInputConstant::RELATION_NUMBER_KEEPER => $mate->getRelationNumberKeeper(),
            JsonInputConstant::UBN => $mate->getUbn(),
            JsonInputConstant::REQUEST_STATE => $mate->getRequestState(),
            JsonInputConstant::IS_HIDDEN => $mate->getIsHidden(),
            JsonInputConstant::IS_OVERWRITTEN => $mate->getIsOverwrittenVersion()
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
