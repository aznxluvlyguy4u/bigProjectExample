<?php


namespace AppBundle\Service\Rvo;

use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareAnimalFlagResponse;
use AppBundle\Exception\Rvo\RvoExternalWorkerException;
use AppBundle\model\Rvo\Request\DiervlagMelding\VastleggenDiervlagMelding;
use AppBundle\model\Rvo\Response\DiervlagMelding\VastleggenDiervlagMeldingResponse;
use AppBundle\Util\CurlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\XmlUtil;
use \DateTime as DateTime;


class RvoDeclareAnimalFlagService extends RvoMeldingenServiceBase implements RvoServiceInterface
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
        // TODO send $xmlRequestBody to EXTERNAL QUEUE
    }


    public function sendRequestToRvo()
    {
        $xmlRequestBody = 'read it from the external queue';
        $curl = $this->post($xmlRequestBody);

        if (!CurlUtil::is200Response($curl)) {
            throw new RvoExternalWorkerException($curl->response, $curl->getHttpStatus());
        }

        $rvoXmlResponseContent = $curl->getResponse();
        // TODO send it to the INTERNAL QUEUE
    }


    public function processResponse()
    {
        $rvoXmlResponseContent = 'read it from the internal queue';
        $rvoResponse = $this->parseRvoResponseObject($rvoXmlResponseContent, VastleggenDiervlagMeldingResponse::class);
        $response = $this->parseNsfoResponse($rvoResponse);
    }


    public function parseNsfoResponse(VastleggenDiervlagMeldingResponse $rvoResponse): DeclareAnimalFlagResponse
    {
        $request = $this->em->getRepository(DeclareAnimalFlag::class)
            ->findOneByRequestId($rvoResponse->requestID);

        $diergegevensDiervlagMeldingResponse = $rvoResponse->diergegevensDiervlagMeldingResponse;
        $verwerkingsResultaat = $diergegevensDiervlagMeldingResponse->verwerkingsresultaat;

        $response = new DeclareAnimalFlagResponse();
        $response->setDeclareAnimalFlagRequestMessage($request);
        $response->setRequestId($rvoResponse->requestID);
        $response->setMessageId($request->getMessageId());
        $response->setMessageNumber($diergegevensDiervlagMeldingResponse->meldingnummer);
        $response->setLogDate(new DateTime());
        $response->setErrorCode($verwerkingsResultaat->foutcode);
        $response->setErrorMessage($verwerkingsResultaat->foutmelding);
        $response->setErrorKindIndicator($verwerkingsResultaat->soortFoutIndicator);
        $response->setSuccessIndicator($verwerkingsResultaat->succesIndicator);
        $response->setIsRemovedByUser(false);
        $response->setActionBy($request->getActionBy());

        return $response;
    }
}