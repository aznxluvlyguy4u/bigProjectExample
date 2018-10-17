<?php


namespace AppBundle\Worker\DirectProcessor;


use AppBundle\Entity\Animal;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareLossResponse;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RvoErrorCode;
use AppBundle\Enumerator\RvoErrorMessage;
use AppBundle\Util\TimeUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

class DeclareLossProcessor extends DeclareProcessorBase implements DeclareLossProcessorInterface
{
    /** @var DeclareLoss */
    private $loss;
    /** @var DeclareLossResponse */
    private $response;
    /** @var Animal */
    private $animal;

    /** @var string */
    private $errorMessage;
    /** @var string */
    private $errorCode;

    function process(DeclareLoss $loss)
    {
        $this->loss = $loss;
        $this->animal = $loss->getAnimal();

        $this->response = new DeclareLossResponse();
        $this->response->setDeclareLossIncludingAllValues($this->loss);

        $status = $this->getRequestStateAndSetErrorData();

        switch ($status) {
            case RequestStateType::FINISHED:
                $this->processSuccessLogic();
                break;

            case RequestStateType::FAILED:
                $this->processFailedLogic();
                break;

            case RequestStateType::FINISHED_WITH_WARNING:
                $this->processSuccessLogic();
                $this->loss->setFinishedWithWarningRequestState();
                break;

            default: throw new PreconditionFailedHttpException('Invalid requestState: '.$status);
        }

        $this->getManager()->persist($this->loss);
        $this->getManager()->persist($this->response);
        $this->getManager()->flush();

        $this->loss = null;
        $this->response = null;
        $this->animal = null;
        $this->errorMessage = null;
        $this->errorCode = null;

        return $this->getDeclareMessageArray($loss, false);
    }


    private function getRequestStateAndSetErrorData()
    {
        $this->errorMessage = '';
        $this->errorCode = '';
        if (!$this->animal->isOnLocation($this->loss->getLocation())) {
            $this->errorMessage = $this->translator->trans('ANIMAL WAS NOT FOUND ON UBN').': '.$this->loss->getUbn();
            $this->errorCode = Response::HTTP_PRECONDITION_REQUIRED;
            return RequestStateType::FAILED;
        }

        if ($this->animal->getIsAlive() || !$this->animal->getDateOfDeath()) {
            return RequestStateType::FINISHED;
        }

        if (TimeUtil::isDateTimesOnTheSameDay($this->animal->getDateOfDeath(), $this->loss->getDateOfDeath())) {
            $this->response->setWarningValues(
                RvoErrorMessage::REPEATED_LOSS_00185,
                RvoErrorCode::REPEATED_LOSS_00185
            );
            return RequestStateType::FINISHED_WITH_WARNING;
        }

        $this->errorMessage = $this->translator->trans('ANIMAL ALREADY HAS A DIFFERENT DATE OF DEATH');
        $this->errorCode = Response::HTTP_PRECONDITION_REQUIRED;
        return RequestStateType::FAILED;
    }


    private function processSuccessLogic()
    {
        $this->loss->setFinishedRequestState();
        $this->response->setSuccessValues();

        $this->animal->setIsAlive(false);
        $this->animal->setTransferState(null);
        $this->animal->setDateOfDeath($this->loss->getDateOfDeath());

        $this->closeLastOpenAnimalResidence($this->animal, $this->loss->getLocation(), $this->loss->getDateOfDeath());

        $this->getManager()->persist($this->animal);

    }

    private function processFailedLogic()
    {
        $this->loss->setFailedRequestState();
        $this->response->setFailedValues($this->errorMessage, $this->errorCode);
    }


}