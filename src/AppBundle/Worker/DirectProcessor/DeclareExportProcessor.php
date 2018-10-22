<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareExportResponse;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\TagStateType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class DeclareExportProcessor extends DeclareProcessorBase implements DeclareExportProcessorInterface
{
    /** @var DeclareExport */
    private $export;
    /** @var DeclareExportResponse */
    private $response;
    /** @var Animal */
    private $animal;

    public function process(DeclareExport $export): array
    {
        $this->export = $export;
        $this->animal = $export->getAnimal();

        $this->response = new DeclareExportResponse();
        $this->response->setDeclareExportIncludingAllValues($this->export);

        if ($this->animal->isOnLocation($this->export->getLocation())) {
            $status = RequestStateType::FINISHED;
        } else {
            $status = RequestStateType::FAILED;
        }

        switch ($status) {
            case RequestStateType::FINISHED:
                $this->processSuccessLogic();
                break;

            case RequestStateType::FAILED:
                $this->processFailedLogic();
                break;

            case RequestStateType::FINISHED_WITH_WARNING:
                $this->processSuccessLogic();
                $this->export->setFinishedWithWarningRequestState();
                break;

            default: throw new PreconditionFailedHttpException('Invalid requestState: '.$status);
        }

        $this->persistResponseInSeparateTransaction($this->response);

        $this->getManager()->persist($this->export);
        $this->getManager()->flush();

        $this->export = null;
        $this->response = null;
        $this->animal = null;

        return $this->getDeclareMessageArray($export, false);
    }

    private function processSuccessLogic()
    {
        $this->export->setFinishedRequestState();
        $this->response->setSuccessValues();

        $this->animal->setTransferredTransferState();
        $this->animal->setIsExportAnimal(true);

        $this->closeLastOpenAnimalResidence(
            $this->export->getAnimal(),
            $this->export->getLocation(),
            $this->export->getExportDate()
        );

        $this->animal->setLocation(null);
        $this->export->getLocation()->removeAnimal($this->animal);

        $tag = $this->findExportTag();
        if ($tag) {
            $this->getManager()->remove($tag);
        }

        $this->getManager()->persist($this->export->getLocation());
        $this->getManager()->persist($this->animal);

    }

    private function processFailedLogic()
    {
        $this->export->setFailedRequestState();
        $this->response->setFailedValues(
            $this->translator->trans('ANIMAL WAS NOT FOUND ON UBN').': '.$this->export->getUbn(),
            Response::HTTP_PRECONDITION_REQUIRED
        );

        $tag = $this->findExportTag();
        if ($tag->getTagStatus() !== TagStateType::UNASSIGNED) {
            $tag->setTagStatus(TagStateType::UNASSIGNED);
            $this->getManager()->persist($tag);
        }
    }

    private function findExportTag(): ?Tag
    {
        return $this->findTag(
            $this->animal->getUlnCountryCode(),
            $this->animal->getUlnNumber()
        );
    }
}