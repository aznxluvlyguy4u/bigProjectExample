<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Client;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Pedigree;
use Doctrine\Common\Collections\Collection;
use AppBundle\Component\Count;

class MateOutput
{
    /**
     * @return array
     */
    public static function createMatesOverview()
    {
        
    }

    /**
     * @param Mate $mate
     *
     * @return array
     */
    public static function createMateOverview($mate)
    {
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
                JsonInputConstant::ULN_COUNTRY_CODE => $mate->getStudEwe()->getUlnCountryCode(),
                JsonInputConstant::ULN_NUMBER => $mate->getStudEwe()->getUlnNumber()
            ],
            JsonInputConstant::RELATION_NUMBER_KEEPER => $mate->getRelationNumberKeeper(),
            JsonInputConstant::UBN => $mate->getUbn(),
            JsonInputConstant::REQUEST_STATE => $mate->getRequestState(),
            JsonInputConstant::IS_HIDDEN => $mate->getIsHidden(),
            JsonInputConstant::IS_OVERWRITTEN => $mate->getIsOverwrittenVersion()
        ];

        return $res;
    }
}
