<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareArrivalResponse;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RvoErrorCode;
use AppBundle\Enumerator\RvoErrorMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class DeclareArrivalProcessor extends DeclareProcessorBase implements DeclareArrivalProcessorInterface
{
    /** @var DeclareArrival */
    private $arrival;
    /** @var DeclareArrivalResponse */
    private $response;
    /** @var Animal */
    private $animal;
    /** @var Location */
    private $origin;
    /** @var bool */
    private $clearCache;


    /**
     * @param DeclareArrival $arrival
     * @param Location $origin
     * @return array
     */
    function process(DeclareArrival $arrival, ?Location $origin)
    {
        $this->getManager()->persist($arrival);
        $this->getManager()->flush();
        $this->getManager()->refresh($arrival);

        $this->arrival = $arrival;
        $this->animal = $arrival->getAnimal();
        $this->origin = $origin;

        $this->response = new DeclareArrivalResponse();
        $this->response->setDeclareArrivalIncludingAllValues($arrival);

        $status = $this->getRequestStateAndSetResponseData();

        switch ($status) {
            case RequestStateType::FINISHED:
                $this->processSuccessLogic();
                break;

            case RequestStateType::FAILED:
                $this->processFailedLogic();
                break;

            case RequestStateType::FINISHED_WITH_WARNING:
                $this->processSuccessWithWarning();
                break;

            default: throw new PreconditionFailedHttpException('Invalid requestState: '.$status);
        }

        $this->persistResponseInSeparateTransaction($this->response);

        $this->getManager()->persist($this->arrival);
        $this->getManager()->flush();

        if ($this->clearCache) {
            $this->clearLivestockCacheForLocation($this->arrival->getLocation());
            $this->clearLivestockCacheForLocation($this->origin);
        }

        $this->animal = null;
        $this->arrival = null;
        $this->response = null;
        $this->clearCache = null;
        $this->origin = null;

        return $this->getDeclareMessageArray($arrival, false);
    }


    private function getRequestStateAndSetResponseData()
    {
        if ($this->animal->isDead()) {
            $this->response->setFailedValues(
                $this->translator->trans('ANIMAL IS ALREADY DEAD'),
                Response::HTTP_PRECONDITION_REQUIRED
            );
            return RequestStateType::FAILED;
        }

        $animalIsOnOriginLocation = $this->animal->isOnLocation($this->arrival->getLocation());

        if ($animalIsOnOriginLocation) {
            $this->response->setWarningValues(
                RvoErrorMessage::REPEATED_ARRIVAL_00015_MAIN_PART,
                RvoErrorCode::REPEATED_ARRIVAL_00015
            );
            return RequestStateType::FINISHED_WITH_WARNING;
        }

        $this->response->setSuccessValues();
        return RequestStateType::FINISHED;
    }


    private function processSuccessLogic()
    {
        if ($this->origin) {
            $finalizeTransaction = $this->animalResidenceOnPreviousLocationHasBeenFinalized($this->animal, $this->origin);
        } else {
            $finalizeTransaction = true;
        }

        $destination = $this->arrival->getLocation();

        $this->createNewAnimalResidenceIfNotExistsYet(
            $this->animal,
            $destination,
            $this->arrival->getArrivalDate(),
            !$finalizeTransaction
        );

        $this->animal->setTransferState(null);
        $this->animal->setIsExportAnimal(false);
        $this->animal->setIsDepartedAnimal(false);
        $this->animal->setIsAlive(true);

        $this->animal->setLocation($destination);

        $this->getManager()->persist($this->animal);

        $this->displayDeclareNotificationMessage($this->arrival, $this->response);

        $this->arrival->setFinishedRequestState();
        $this->clearCache = true;
    }


    private function processFailedLogic()
    {
        $this->arrival->setFailedRequestState();
        $this->clearCache = true;
    }


    private function processSuccessWithWarning()
    {
        $updateAnimal = false;
        if ($this->animal->getIsExportAnimal()) {
            $this->animal->setIsExportAnimal(false);
            $updateAnimal = true;
        }

        if (!$this->animal->getIsAlive()) {
            $this->animal->setIsAlive(true);
            $updateAnimal = true;
        }

        if ($updateAnimal) {
            $this->getManager()->persist($this->animal);
        }

        $this->arrival->setFinishedWithWarningRequestState();
        $this->clearCache = false;
    }

}