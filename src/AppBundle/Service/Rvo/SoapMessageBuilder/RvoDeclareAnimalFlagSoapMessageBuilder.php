<?php


namespace AppBundle\Service\Rvo\SoapMessageBuilder;

use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\model\Rvo\Request\DiervlagMelding\VastleggenDiervlagMelding;
use AppBundle\Util\StringUtil;
use AppBundle\Util\XmlUtil;


class RvoDeclareAnimalFlagSoapMessageBuilder extends RvoMeldingenSoapMessageBuilderBase
{

    public function sendRequestToExternalQueue(DeclareAnimalFlag $flag)
    {
        $flagJson = json_encode(new VastleggenDiervlagMelding($flag));
        $flagAsAssociativeArray = json_decode($flagJson, true);

        $bodyKey = lcfirst(StringUtil::getEntityName(VastleggenDiervlagMelding::class));

        $body = [
            $bodyKey => $flagAsAssociativeArray
        ];

        $xmlRequestBody = XmlUtil::parseRvoXmlRequestBody($body, self::SOAP_ENVELOPE_DETAILS);
        // TODO send $xmlRequestBody to EXTERNAL QUEUE / Must include request type
    }

}