<?php


namespace AppBundle\Worker\Logic;


use AppBundle\Constant\Constant;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareBaseResponseInterface;
use AppBundle\Entity\Treatment;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RvoErrorCode;
use AppBundle\Enumerator\SuccessIndicator;
use AppBundle\Exception\Rvo\RvoUnmappedResponseDetailsException;
use AppBundle\Exception\Sqs\SqsMessageInvalidTaskTypeException;
use AppBundle\model\Rvo\Response\DiervlagMelding\VastleggenDiervlagMeldingResponse;
use DateTime;

class DeclareAnimalFlagAction extends RawInternalWorkerActionBase implements RawInternalWorkerLogicInterface
{
    public function process(string $rvoXmlResponseContent)
    {
        /** @var VastleggenDiervlagMeldingResponse $rvoResponse */
        $rvoResponse = $this->parseRvoResponseObject($rvoXmlResponseContent, VastleggenDiervlagMeldingResponse::class);

        /** @var DeclareAnimalFlag $declareAnimalFlag */
        $declareAnimalFlag = $this->em->getRepository(DeclareAnimalFlag::class)
            ->findOneByRequestId($rvoResponse->requestID);

        // First make sure the treatmentId exists
        $treatmentId = $declareAnimalFlag->getTreatment()->getId();

        $this->addResponseDetailsAndStatusToDeclare($rvoResponse, $declareAnimalFlag);

        // Change status depending on repeated declare logic
        if ($this->isRepeatedDeclare($declareAnimalFlag)) {

            $oldDeclareAnimalFlag = $this->em->getRepository(DeclareAnimalFlag::class)
                ->findActiveForAnimalId($declareAnimalFlag->getAnimalId(), $declareAnimalFlag->getFlagType());

            if (!$oldDeclareAnimalFlag) {
                // The flag does not exist yet in the database.
                // Create it as a new flag.
                $declareAnimalFlag->setWarningValues(
                    $declareAnimalFlag->getErrorMessage(),
                    $declareAnimalFlag->getErrorCode()
                );
                $declareAnimalFlag->setRequestState(RequestStateType::FINISHED_WITH_WARNING);
            }
            // ELSE just keep it as an error.
        }

        // No further business logic is necessary
        $this->em->persist($declareAnimalFlag);
        $this->em->flush();

        $this->updateTreatmentStatus($treatmentId);
        $this->em->flush();

        $this->logger->debug('DeclareAnimalFlag processed!');
    }

    private function addResponseDetailsAndStatusToDeclare(VastleggenDiervlagMeldingResponse $rvoResponse,
                                                          DeclareAnimalFlag &$declareAnimalFlag)
    {
        $diergegevensDiervlagMeldingResponse = $rvoResponse->diergegevensDiervlagMeldingResponse;
        $verwerkingsResultaat = $diergegevensDiervlagMeldingResponse->verwerkingsresultaat;

        // 1. Copy response values
        $declareAnimalFlag->setMessageNumber($diergegevensDiervlagMeldingResponse->meldingnummer);
        $declareAnimalFlag->setResponseLogDate(new DateTime());
        $declareAnimalFlag->setErrorCode($verwerkingsResultaat->foutcode);
        $declareAnimalFlag->setErrorMessage($verwerkingsResultaat->foutmelding);
        $declareAnimalFlag->setErrorKindIndicator($verwerkingsResultaat->soortFoutIndicator);
        $declareAnimalFlag->setSuccessIndicator($verwerkingsResultaat->succesIndicator);

        // 2. Set request status by response details
        switch (true) {
            // Always check the SuccessWithWarning details BEFORE the Success details
            case $declareAnimalFlag->hasSuccessWithWarningResponse():
                $declareAnimalFlag->setRequestState(RequestStateType::FINISHED_WITH_WARNING);
                break;
            case $declareAnimalFlag->hasSuccessResponse():
                $declareAnimalFlag->setRequestState(RequestStateType::FINISHED);
                break;
            case $declareAnimalFlag->hasFailedResponse():
                $declareAnimalFlag->setRequestState(RequestStateType::FAILED);
                break;
            default:
                throw new RvoUnmappedResponseDetailsException($declareAnimalFlag);
        }
    }


    private function isRepeatedDeclare(DeclareBaseResponseInterface $response)
    {
        $repeatedDeclareErrorCodes = [
            RvoErrorCode::REPEATED_ANIMAL_FLAG_01521
        ];

        return in_array($response->getErrorCode(), $repeatedDeclareErrorCodes);
    }


    private function updateTreatmentStatus(int $treatmentId)
    {
        $successIndicatorCounts = $this->em->getRepository(DeclareAnimalFlag::class)
            ->getFlagSuccessIndicatorCountByTreatmentId($treatmentId);

        if ($successIndicatorCounts[Constant::NULL] !== 0) {
            // Not all declare animal flags have been process yet for the treatment
            return;
        }

        $hasFailedFlags = $successIndicatorCounts[SuccessIndicator::N] > 0;
        $hasSuccessFlags = $successIndicatorCounts[SuccessIndicator::N] > 0;

        $hasOnlySuccessFlags = $hasSuccessFlags && !$hasFailedFlags;
        $hasOnlyFailedFlags = $hasFailedFlags && !$hasSuccessFlags;

        switch (true) {
            case $hasOnlySuccessFlags:
                $finalTreatmentStatus = RequestStateType::FINISHED;
                break;
            case $hasOnlyFailedFlags:
                $finalTreatmentStatus = RequestStateType::FAILED;
                break;
            default:
                $finalTreatmentStatus = RequestStateType::FINISHED_WITH_ERRORS;
                break;
        }

        $treatment = $this->em->getRepository(Treatment::class)->find($treatmentId);
        if ($treatment && $treatment->getStatus() !== $finalTreatmentStatus) {
            $treatment->setStatus($finalTreatmentStatus);
            $this->em->persist($treatment);
        }
    }
}