<?php


namespace AppBundle\Service\Rvo;

use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\model\Rvo\Request\DiervlagMelding\VastleggenDiervlagMelding;
use Symfony\Component\Serializer\Encoder\XmlEncoder;


class RvoDeclareAnimalFlagService extends RvoMeldingenServiceBase implements RvoServiceInterface
{
    public function testRequest()
    {
        $flagOpen = $this->em->getRepository(DeclareAnimalFlag::class)
            ->find(240494);

        $this->sendRequest($flagOpen);
    }

    public function sendRequest(DeclareAnimalFlag $flag)
    {
        $flagJson = json_encode(new VastleggenDiervlagMelding($flag));
        $flagAsAssociativeArray = json_decode($flagJson, true);

        $body = [
            'vastleggenDiervlagMelding' => $flagAsAssociativeArray
        ];


        // Move encoding to base class
        $encoder = new XmlEncoder();

        $soapEnvelope = 'soapenv:Envelope';
        $formatOutput = true;
        $xmlOptions = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8',
            'xml_format_output' => $formatOutput,
            'xml_root_node_name' => $soapEnvelope,
            'remove_empty_tags' => true
        ];

        $xml = $encoder->encode([
            'soapenv:Header' => [],
            'soapenv:Body' => $body,
        ], 'xml', $xmlOptions);

        $headerNewLine = $formatOutput ? "\n  " : ' ';
        $xml = str_replace(
            "<$soapEnvelope",
             "<$soapEnvelope".' xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"'.$headerNewLine
             .'xmlns:mel="http://www.ienr.org/schemas/services/meldingenWS_v2_0"'.$headerNewLine
             .'xmlns:mel1="http://www.ienr.org/schemas/types/meldingen_v2_0"',$xml);

        echo $xml;
        die;

        dump($xml);die;
    }
}