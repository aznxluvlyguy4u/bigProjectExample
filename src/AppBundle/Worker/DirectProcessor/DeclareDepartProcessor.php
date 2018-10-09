<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareDepartResponse;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AnimalTransferStatus;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RvoErrorCode;
use AppBundle\Enumerator\RvoErrorMessage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class DeclareDepartProcessor extends DeclareProcessorBase implements DeclareDepartProcessorInterface
{
    /** @var DeclareDepart */
    private $depart;
    /** @var DeclareDepartResponse */
    private $response;
    /** @var Animal */
    private $animal;
    /** @var Location */
    private $newLocation;
    /** @var bool */
    private $clearCache;

    function process(DeclareDepart $depart)
    {
        $this->depart = $depart;
        $this->animal = $depart->getAnimal();

        $this->response = new DeclareDepartResponse();
        $this->response->setDeclareDepartIncludingAllValues($depart);

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

        $this->depart->addResponse($this->response);
        $this->getManager()->persist($this->response);
        $this->getManager()->persist($this->depart);
        $this->getManager()->flush();

        if ($this->clearCache) {
            $this->clearLivestockCacheForLocation($this->depart->getLocation());
            $this->clearLivestockCacheForLocation($this->newLocation);
        }

        $this->animal = null;
        $this->depart = null;
        $this->response = null;
        $this->clearCache = null;
        $this->newLocation = null;

        return $this->getDeclareMessageArrayAndJsonMessage($depart, false);
    }

    private function getRequestStateAndSetResponseData()
    {
        $animalIsOnOriginLocation = $this->animal->isOnLocation($this->depart->getLocation());

        if (!$animalIsOnOriginLocation) {
            if ($this->animal->getUbn() === $this->depart->getUbnNewOwner() && $this->animal->getUbn() !== null) {
                $this->response->setWarningValues(
                    RvoErrorMessage::REPEATED_DEPART_00184,
                    RvoErrorCode::REPEATED_DEPART_00184
                );
                return RequestStateType::FINISHED_WITH_WARNING;
            }

            $this->response->setFailedValues(
                $this->translator->trans('ANIMAL WAS NOT FOUND ON UBN').': '.$this->depart->getUbn(),
                Response::HTTP_PRECONDITION_REQUIRED
            );
            return RequestStateType::FAILED;
        }

        if ($this->animal->isDeclaredDead() && $animalIsOnOriginLocation) {
            $this->response->setFailedValues(
                $this->translator->trans('ANIMAL IS ALREADY DEAD'),
                Response::HTTP_PRECONDITION_REQUIRED
            );
            return RequestStateType::FAILED;
        }

        $this->response->setSuccessValues();
        return RequestStateType::FINISHED;
    }


    private function processSuccessLogic()
    {
        $this->animal->setTransferState(AnimalTransferStatus::TRANSFERRED);
        $this->animal->setIsExportAnimal(false);
        $this->animal->setLocation(null);
        $this->getManager()->persist($this->animal);

        $this->closeLastOpenAnimalResidence($this->animal, $this->depart->getLocation(), $this->depart->getDepartDate());
        $this->newLocation = $this->getManager()->getRepository(Location::class)
            ->findOneByActiveUbn($this->depart->getUbnNewOwner());
        $this->finalizeAnimalTransferAndAnimalResidenceDestination($this->animal, $this->newLocation);
        $this->displayDeclareNotificationMessage($this->depart, $this->response);

        $this->depart->setFinishedRequestState();
        $this->clearCache = true;
    }


    private function processFailedLogic()
    {
        $this->animal->setTransferState(null);
        $this->animal->setIsDepartedAnimal(false);
        $this->animal->setIsExportAnimal(false);
        $this->getManager()->persist($this->animal);

        $this->resetOriginPendingStateAnimalResidence($this->animal, $this->depart->getLocation());
        $this->depart->setFailedRequestState();
        $this->clearCache = true;
    }


    private function processSuccessWithWarning()
    {
        if ($this->animal->getIsExportAnimal()) {
            $this->animal->setIsExportAnimal(false);
            $this->getManager()->persist($this->animal);
        }

        $this->depart->setFinishedWithWarningRequestState();
        $this->clearCache = false;
    }
}