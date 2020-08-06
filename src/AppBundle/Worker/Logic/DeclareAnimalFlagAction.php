<?php


namespace AppBundle\Worker\Logic;


use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\model\Rvo\Response\DiervlagMelding\VastleggenDiervlagMeldingResponse;
use DateTime;

class DeclareAnimalFlagAction extends InternalWorkerActionBase implements InternalWorkerLogicInterface
{
    /**
     * @param string $rvoXmlResponseContent TODO read it from the internal queue
     */
    public function process(string $rvoXmlResponseContent)
    {
        /** @var VastleggenDiervlagMeldingResponse $rvoResponse */
        $rvoResponse = $this->parseRvoResponseObject($rvoXmlResponseContent, VastleggenDiervlagMeldingResponse::class);

        $declareAnimalFlag = $this->em->getRepository(DeclareAnimalFlag::class)
            ->findOneByRequestId($rvoResponse->requestID);

        $response = $this->addResponseDetailsToDeclare($rvoResponse);

        // TODO add business logic here and persist request and response

        // TODO delete message from queue
    }

    private function addResponseDetailsToDeclare(VastleggenDiervlagMeldingResponse $rvoResponse,
                                                 DeclareAnimalFlag &$declareAnimalFlag)
    {
        $diergegevensDiervlagMeldingResponse = $rvoResponse->diergegevensDiervlagMeldingResponse;
        $verwerkingsResultaat = $diergegevensDiervlagMeldingResponse->verwerkingsresultaat;

        $declareAnimalFlag->setMessageNumber($diergegevensDiervlagMeldingResponse->meldingnummer);
        $declareAnimalFlag->setResponseLogDate(new DateTime());
        $declareAnimalFlag->setErrorCode($verwerkingsResultaat->foutcode);
        $declareAnimalFlag->setErrorMessage($verwerkingsResultaat->foutmelding);
        $declareAnimalFlag->setErrorKindIndicator($verwerkingsResultaat->soortFoutIndicator);
        $declareAnimalFlag->setSuccessIndicator($verwerkingsResultaat->succesIndicator);
    }


}