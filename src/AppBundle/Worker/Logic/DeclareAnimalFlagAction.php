<?php


namespace AppBundle\Worker\Logic;


use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareAnimalFlagResponse;
use AppBundle\model\Rvo\Response\DiervlagMelding\VastleggenDiervlagMeldingResponse;

class DeclareAnimalFlagAction extends InternalWorkerActionBase implements InternalWorkerLogicInterface
{
    /**
     * @param string $rvoXmlResponseContent TODO read it from the internal queue
     */
    public function process(string $rvoXmlResponseContent)
    {
        $rvoResponse = $this->parseRvoResponseObject($rvoXmlResponseContent, VastleggenDiervlagMeldingResponse::class);
        $response = $this->parseNsfoResponse($rvoResponse);
        // TODO add business logic here and persist request and response

        // TODO delete message from queue
    }

    private function parseNsfoResponse(VastleggenDiervlagMeldingResponse $rvoResponse): DeclareAnimalFlagResponse
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