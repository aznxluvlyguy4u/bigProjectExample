<?php

namespace AppBundle\Service;

use AppBundle\Component\AnimalFlagMessageBuilder;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\TreatmentAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\Entity\DeclareAnimalFlagResponse;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\MedicationSelection;
use AppBundle\Entity\Person;
use AppBundle\Entity\QFever;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use DateInterval;
use DateTime;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Class TreatmentService
 * @package AppBundle\Service
 */
class TreatmentService extends TreatmentServiceBase implements TreatmentAPIControllerInterface
{

    /** @var AnimalFlagMessageBuilder */
    private $animalFlagMessageBuilder;

    /** @var QFeverService */
    private $qFeverService;

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    function createIndividualTreatment(Request $request)
    {
        return $this->createTreatment($request, TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    function createLocationTreatment(Request $request)
    {
        return $this->createTreatment($request, TreatmentTypeOption::LOCATION);
    }

    /**
     * @param Request $request
     * @param $type
     * @return JsonResponse
     * @throws Exception
     */
    private function createTreatment(Request $request, $type)
    {
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $em = $this->getManager();

        SqlUtil::bumpPrimaryKeySeqIfTooLow($this->getConnection(), DeclareBaseResponse::getTableName());

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        /** @var Treatment $treatment */
        $treatment = $this->getBaseSerializer()->deserializeToObject($request->getContent(), Treatment::class);

        if (!($treatment instanceof Treatment)) {
            throw new PreconditionFailedHttpException("Json body must have the Treatment structure");
        }

        $treatment
            ->setType($type)
            ->setLocation($location)
            ->setCreateDate(new DateTime());

        //Validation
        $treatment = $this->baseValidateDeserializedTreatment($treatment);
        if ($treatment instanceof JsonResponse) { return $treatment; }

        /** @var TreatmentTemplate $treatmentTemplate */
        $treatmentTemplate = $em->getRepository(TreatmentTemplate::class)->find($treatment->getTreatmentTemplate()->getId());

        $historicAnimals = $em->getRepository(Animal::class)
            ->getHistoricLiveStock($location, $this->getCacheService(), $this->getBaseSerializer());

        $historicAnimalsIds = array_map(function (Animal $animal) {
            return $animal->getId();
        }, $historicAnimals);

        /** @var ArrayCollection<Animal> $existingAnimals */
        $existingAnimals = new ArrayCollection();

        /** @var Animal $animal */
        foreach ($treatment->getAnimals() as $animal) {
            $animalId = $animal->getId();

            /** @var Animal $existingAnimal */
            $existingAnimal = $em->getRepository(Animal::class)->find($animalId);

            if ($existingAnimal !== null || in_array($animalId, $historicAnimalsIds)) {
                $existingAnimals->add($existingAnimal);
            } else {
                throw new PreconditionFailedHttpException("Animal with id ".$animalId." not found");
            }
        }

        $this->validateQFeverFlagsForAllAnimals($treatmentTemplate, $existingAnimals); //TODO

        // No duplicates are being created, so what is being meant with "duplicates"?
        //TODO check for duplicates

        // First complete all the validation before sending any messages to RVO!

        $medicationSelections = new ArrayCollection();

        /** @var TreatmentMedication $treatmentMedication */
        foreach ($treatment->getTreatmentTemplate()->getMedications() as $treatmentMedication) {
            /** @var TreatmentMedication $treatmentMedicationInDB */
            $treatmentMedicationInDB = $this->getManager()
                ->getRepository(TreatmentMedication::class)->find($treatmentMedication->getId());

            if (!$treatmentMedicationInDB) {
                throw new PreconditionFailedHttpException('Medication with '. $treatmentMedication->getId(). 'does not exist.');
            }

            $treatmentDuration = $treatmentMedicationInDB->getTreatmentDuration();
            $medicationSelection = new MedicationSelection();

            $medicationSelection
                ->setTreatment($treatment)
                ->setTreatmentMedication($treatmentMedicationInDB)
            ;

            if ($treatmentDuration !== 'eenmalig') {
                $roundedTreatmentDuration = round($treatmentDuration, 0, PHP_ROUND_HALF_UP);

                // Subtract 1 to account for the start day of the treatment.
                $correctedTreatmentDuration = $roundedTreatmentDuration-1;

                $daysToAdd = $correctedTreatmentDuration + $treatmentMedicationInDB->getWaitingDays();

                $treatmentStartDate = clone $treatment->getStartDate();
                if ($daysToAdd > 0) {
                    $treatmentStartDate->add(new DateInterval('P'.$daysToAdd.'D'));
                }

                $medicationSelection
                    ->setWaitingTimeEnd($treatmentStartDate);
            } else {
                $treatmentStartDate = clone $treatment->getStartDate();
                $medicationSelection
                    ->setWaitingTimeEnd($treatmentStartDate->add(new DateInterval('P'.$treatmentMedicationInDB->getWaitingDays().'D')));
            }

            $medicationSelections->add($medicationSelection);

            $em->persist($medicationSelection);
        }

        $treatment->__construct();

        $treatment
            ->setMedicationSelections($medicationSelections)
            ->setCreationBy($this->getUser())
            ->setAnimals($existingAnimals)
            ->setTreatmentTemplate($treatmentTemplate);

        $em->persist($treatment);

        try {

            if ($treatmentTemplate instanceof QFever) {
                if ($location->isDutchLocation()) {
                    // The treatment has to be persisted first before being able to persist DeclareAnimalFlag
                    $this->createAndSendQFeverRvoMessages($treatment, $treatmentTemplate, $existingAnimals, $client, $loggedInUser);
                } else {
                    $this->createCompletedQFeverMessage($treatment, $loggedInUser, $existingAnimals);
                }
            }

            $em->flush();

        } catch (\Exception $exception) {
            if ($exception instanceof UniqueConstraintViolationException) {

                $this->resetManager();

                SqlUtil::bumpPrimaryKeySeq($this->getConnection(), DeclareBase::getTableName());
                SqlUtil::bumpPrimaryKeySeq($this->getConnection(), DeclareBaseResponse::getTableName());
            }
            throw $exception;
        }


        ActionLogWriter::createTreatment($em, $request, $loggedInUser, $treatment);

        $output = $this->getBaseSerializer()->getDecodedJson($treatment, $this->getJmsGroupByQueryForTreatment($request));

        return ResultUtil::successResult($output);
    }

    private function createCompletedQFeverMessage(Treatment $treatment, $loggedInUser, ArrayCollection $existingAnimals)
    {
        $template = $treatment->getTreatmentTemplate();
        $em = $this->getManager();
        $flagType = QFeverService::getFlagType($template->getDescription(), $template->getAnimalType());

        /** @var Animal $animal */
        foreach ($existingAnimals as $animal) {
            $treatment->setStatus(RequestStateType::FINISHED);

            $declareAnimalFlag = (new DeclareAnimalFlag())
                ->setAnimal($animal)
                ->setLocation($treatment->getLocation())
                ->setFlagType($flagType)
                ->setFlagStartDate($treatment->getStartDate())
                ->setFlagEndDate($treatment->getEndDate())
                ->setTreatment($treatment);

            $declareAnimalFlag = $this->animalFlagMessageBuilder->buildMessage(
                $declareAnimalFlag,
                $treatment->getLocation()->getOwner(),
                $loggedInUser,
                $treatment->getLocation()
            );

            // The request state should be set after the animalFlagMessageBuilder where it is set to OPEN.
            $declareAnimalFlag->setRequestState(RequestStateType::FINISHED);

            $declareAnimalFlagResponse = new DeclareAnimalFlagResponse();

            $declareAnimalFlagResponse
                ->setRequestId($declareAnimalFlag->getRequestId())
                ->setMessageId($declareAnimalFlag->getMessageId())
                ->setLogDate(new DateTime())
                ->setActionBy(($loggedInUser instanceof Person) ? $loggedInUser : null)
                ->setIsRemovedByUser(false)
                ->setSuccessValues()
                ->setDeclareAnimalFlagRequestMessage($declareAnimalFlag);

            $em->persist($declareAnimalFlag);
            $em->persist($declareAnimalFlagResponse);
        }

        $em->flush();
    }

    private function validateQFeverFlagsForAllAnimals(
        TreatmentTemplate $template,
        ArrayCollection $existingAnimals
    )
    {
        $flagType = QFeverService::getFlagType($template->getDescription(), $template->getAnimalType());
        foreach ($existingAnimals as $existingAnimal) {
            // TODO check if existing animal already has the $flagType. Throw exception in that case.
        }
    }


    private function createAndSendQFeverRvoMessages(
        Treatment $treatment, TreatmentTemplate $template, ArrayCollection $existingAnimals,
        Client $client, Person $loggedInUser)
    {
        $isQFeverTreatment = $this->qFeverService->isQFeverDescription($treatment->getDescription());

        if ($isQFeverTreatment && $template instanceof QFever) {

            $flagType = QFeverService::getFlagType($template->getDescription(), $template->getAnimalType());

            foreach ($existingAnimals as $existingAnimal) {

                $treatment->setStatus(RequestStateType::OPEN);

                $declareAnimalFlag = (new DeclareAnimalFlag())
                    ->setAnimal($existingAnimal)
                    ->setLocation($treatment->getLocation())
                    ->setFlagType($flagType)
                    ->setFlagStartDate($treatment->getStartDate())
                    ->setFlagEndDate($treatment->getEndDate())
                    ->setTreatment($treatment)
                ;

                $messageObject = $this->animalFlagMessageBuilder->buildMessage($declareAnimalFlag, $client,
                    $loggedInUser, $treatment->getLocation());

                $this->getManager()->persist($messageObject);
                $this->getManager()->flush();

                $this->sendMessageObjectToQueue($messageObject);
            }
        }
    }


    /**
     * @param Treatment $treatment
     * @return JsonResponse|Treatment
     * @throws Exception
     */
    private function baseValidateDeserializedTreatment(Treatment $treatment)
    {
        $locationRequested = $treatment->getLocation();
        $location = null;
        if ($locationRequested) {
            $location = $this->getLocationByUbn($locationRequested->getUbn());
            if ($location instanceof JsonResponse) { return $location; }
        }

        $treatmentTemplateId = $treatment->getTreatmentTemplate()->getId();

        /** @var TreatmentTemplate $treatmentTemplate */
        $treatmentTemplate = $this->treatmentTemplateRepository->find($treatmentTemplateId);

        if ($treatmentTemplate === null) {
            throw new PreconditionFailedHttpException("No treatment template found with id: ". $treatmentTemplateId);
        }

        $description = $treatment->getDescription();
        if ($description === null) {
            throw new PreconditionFailedHttpException("Description is missing");
        }

        if (TimeUtil::isDate1BeforeDate2($treatment->getEndDate(), $treatment->getStartDate())) {
            throw new PreconditionFailedHttpException($this->translator->trans('date.range.inverted'));
        }

        $type = TreatmentTypeService::getValidateType($treatment->getType());
        if ($type instanceof JsonResponse) { return $type; }

        $treatment
            ->setLocation($location)
            ->setType($type);

        return $treatment;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getHistoricTreatments(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        $pageNumber = RequestUtil::getPageNumber($request);
        $pageSize = RequestUtil::getPageSize($request);
        $searchQuery = $request->get('query', '');

        $count = $this->getManager()->getRepository(Treatment::class)
            ->getHistoricTreatmentsTotalCount($location->getUbn(), $searchQuery);

        $treatments = $this->getManager()->getRepository(Treatment::class)
            ->getHistoricTreatments($location->getUbn(), $pageNumber, $pageSize, $searchQuery);

        return ResultUtil::successResult(
                    [
                        'items'      => $treatments,
                        'totalItems' => $count,
                        'itemsOnPage' => count($treatments),
                        'pageNumber' => $pageNumber,
                        'pageSize' => $pageSize,
                    ]
        );
    }

    /**
     * @param $treatment_id
     * @param Request $request
     * @return JsonResponse|Treatment
     * @throws Exception
     */
    function revokeTreatment($treatment_id, Request $request)
    {
        /** @var Treatment $treatment */
        $treatment = $this->getManager()->getRepository(Treatment::class)
            ->find($treatment_id);

        $treatment
            ->setStatus(RequestStateType::REVOKED)
            ->setRevokeDate(new DateTime())
            ->setRevokedBy($this->getUser());

        $this->getManager()->persist($treatment);
        $this->getManager()->flush();

        $output = $this->getBaseSerializer()->getDecodedJson($treatment, $this->getJmsGroupByQueryForTreatment($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     * @throws Exception
     */
    public function editTreatment(Request $request, $treatmentId)
    {
        $em = $this->getManager();

        /** @var Treatment $treatment */
        $treatment = $em->getRepository(Treatment::class)->find($treatmentId);

        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        if ($treatment->getLocation()->getId() !== $location->getId()) {
            throw new PreconditionFailedHttpException('This treatment does not belong to the location with ubn: '.$location->getUbn());
        }

        $content = RequestUtil::getContentAsArrayCollection($request);

        $startDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::START_DATE, $content);
        $endDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::END_DATE, $content);

        $treatment
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        //Validation
        $treatment = $this->baseValidateDeserializedTreatment($treatment);
        if ($treatment instanceof JsonResponse) { return $treatment; }

        $em->persist($treatment);
        $em->flush();

        ActionLogWriter::editTreatment($em, $request, $loggedInUser, $treatment);

        $output = $this->getBaseSerializer()->getDecodedJson($treatment, $this->getJmsGroupByQueryForTreatment($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTreatmentErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

        $declareTreatments = $this->getManager()->getRepository(Treatment::class)->getTreatmentsWithLastErrorResponses($location);

        return ResultUtil::successResult($declareTreatments);
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function deleteIndividualTreatment(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function deleteLocationTreatment(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }

    function getIndividualTreatments(Request $request)
    {
        // TODO: Implement getIndividualTreatments() method.
    }

    function getLocationTreatments(Request $request)
    {
        // TODO: Implement getLocationTreatments() method.
    }

    /**
     * @required Set at initialization
     *
     * @param $animalFlagMessageBuilder
     */
    public function setAnimalFlagMessageBuilder(AnimalFlagMessageBuilder $animalFlagMessageBuilder)
    {
        $this->animalFlagMessageBuilder = $animalFlagMessageBuilder;
    }

    /**
     * @required Set at initialization
     *
     * @param  QFeverService  $qFeverService
     */
    public function setQFeverService(QFeverService $qFeverService)
    {
        $this->qFeverService = $qFeverService;
    }
}
