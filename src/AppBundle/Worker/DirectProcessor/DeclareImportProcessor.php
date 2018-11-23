<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareImportResponse;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\DateTimeFormats;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RvoErrorCode;
use AppBundle\Enumerator\RvoErrorMessage;
use AppBundle\Enumerator\TagStateType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class DeclareImportProcessor extends DeclareProcessorBase implements DeclareImportProcessorInterface
{
    /** @var DeclareImport */
    private $import;
    /** @var DeclareImportResponse */
    private $response;
    /** @var Animal */
    private $animal;
    /** @var Tag */
    private $importTag;

    /** @var boolean */
    private $animalIsOnLocationOfClient;
    /** @var boolean */
    private $animalIsOnAnotherLocation;

    function process(DeclareImport $import)
    {
        $this->getManager()->persist($import);
        $this->getManager()->flush();
        $this->getManager()->refresh($import);

        $this->import = $import;

        $this->response = new DeclareImportResponse();
        $this->response->setDeclareImportIncludingAllValues($this->import);

        if ($import->getAnimal()) {
            $this->animal = $import->getAnimal();
            $this->animalIsOnLocationOfClient = $this->animal->isOnLocation($import->getLocation());
            $this->animalIsOnAnotherLocation = $this->animal->hasLocation() && !$this->animalIsOnLocationOfClient;
        } else {
            $this->animal = $this->createNewAnimal(
                $this->import->getUlnCountryCode(),
                $this->import->getUlnNumber(),
                null
            );
            $this->animalIsOnLocationOfClient = false;
            $this->animalIsOnAnotherLocation = false;
        }

        $this->importTag = $this->findImportTag();

        $status = $this->getRequestState();
        switch ($status) {
            case RequestStateType::FINISHED:
                $this->processSuccessLogic();
                break;

            case RequestStateType::FAILED:
                $this->processFailedLogic();
                break;

            case RequestStateType::FINISHED_WITH_WARNING:
                $this->processSuccessWithWarningLogic();
                break;

            default: throw new PreconditionFailedHttpException('Invalid requestState: '.$status);
        }

        $this->persistResponseInSeparateTransaction($this->response);

        $this->getManager()->persist($this->import);
        $this->getManager()->flush();

        $this->import = null;
        $this->response = null;
        $this->animal = null;
        $this->importTag = null;
        $this->animalIsOnLocationOfClient = null;
        $this->animalIsOnAnotherLocation = null;

        return $this->getDeclareMessageArray($import, false);
    }

    /**
     * @return string
     */
    private function getRequestState(): string
    {
        if (
            $this->animal->isDead() ||
            $this->animalIsOnAnotherLocation
        ) {
            return RequestStateType::FAILED;
        }

        if ($this->animalIsOnLocationOfClient && $this->animal->getIsAlive()) {
            return RequestStateType::FINISHED_WITH_WARNING;
        }
        return RequestStateType::FINISHED;
    }


    private function processSuccessLogic()
    {
        $this->import->setFinishedRequestState();
        $this->response->setSuccessValues();

        $this->animal->setTransferState(null);
        $this->animal->setIsExportAnimal(false);
        $this->animal->setIsDepartedAnimal(false);
        $this->animal->setIsAlive(true);
        $this->animal->setDateOfDeath(null);

        $location = $this->import->getLocation();
        $this->animal->setLocation($location);
        $location->addAnimal($this->animal);

        if ($this->importTag) {
            $this->getManager()->remove($this->importTag);
        }

        $animalResidence = new AnimalResidence($location->getCountryCode(), false);
        $animalResidence->setAnimal($this->animal);
        $animalResidence->setLocation($location);
        $animalResidence->setStartDate($this->import->getImportDate());
        $location->addAnimalResidenceHistory($animalResidence);
        $this->animal->addAnimalResidenceHistory($animalResidence);

        $this->getManager()->persist($animalResidence);
        $this->getManager()->persist($location);
        $this->getManager()->persist($this->animal);

    }

    private function processSuccessWithWarningLogic()
    {
        $this->import->setFinishedWithWarningRequestState();
        $this->response->setWarningValues(
            RvoErrorMessage::REPEATED_LOSS_00185,
            RvoErrorCode::REPEATED_LOSS_00185)
        ;

        $this->animal->setTransferState(null);
        $this->animal->setIsExportAnimal(false);
        $this->animal->setIsDepartedAnimal(false);
        $this->animal->setIsAlive(true);
        $this->animal->setDateOfDeath(null);

        $this->getManager()->persist($this->animal);
    }

    private function processFailedLogic()
    {
        $this->import->setFailedRequestState();

        $errorMessages = [];
        if ($this->animal->isDead()) {
            $errorMessages[] = $this->translator->trans('ANIMAL HAS ALREADY BEEN DECLARED DEAD').'. '
                .$this->translator->trans('DATE_OF_DEATH').': '.$this->animal->getDateOfDeathString(DateTimeFormats::DAY_MONTH_YEAR);
        }

        if ($this->animalIsOnAnotherLocation) {
            $errorMessages[] = $this->translator->trans('ANIMAL IS STILL ON A DIFFERENT LOCATION').'.'
            .' '.$this->translator->trans('CURRENT_UBN').': '.$this->animal->getUbn();
        }

        $this->response->setFailedValues(
            implode('. ', $errorMessages),
            Response::HTTP_PRECONDITION_REQUIRED
        );

        if ($this->importTag && $this->importTag->getTagStatus() !== TagStateType::UNASSIGNED) {
            $this->importTag->setTagStatus(TagStateType::UNASSIGNED);
            $this->getManager()->persist($this->importTag);
        }

        $this->import->setAnimal(null);
    }

    private function findImportTag(): ?Tag
    {
        return $this->findTag(
            $this->animal->getUlnCountryCode(),
            $this->animal->getUlnNumber()
        );
    }
}