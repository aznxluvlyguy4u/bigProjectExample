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
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseResponse;
use AppBundle\Entity\MedicationSelection;
use AppBundle\Entity\Person;
use AppBundle\Entity\QFever;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Enumerator\NsfoErrorCode;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Service\Rvo\SoapMessageBuilder\RvoDeclareAnimalFlagSoapMessageBuilder;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\DateUtil;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Class TreatmentService
 * @package AppBundle\Service
 */
class TreatmentService extends TreatmentServiceBase implements TreatmentAPIControllerInterface
{
    /** @var RvoDeclareAnimalFlagSoapMessageBuilder */
    private $animalFlagSoapMessageBuilder;

    /** @var AnimalFlagMessageBuilder */
    private $animalFlagMessageBuilder;

    /** @var QFeverService */
    private $qFeverService;

    /**
     * @required
     *
     * @param RvoDeclareAnimalFlagSoapMessageBuilder $animalFlagSoapMessageBuilder
     */
    public function setRvoDeclareAnimalFlagSoapMessageBuilder(RvoDeclareAnimalFlagSoapMessageBuilder $animalFlagSoapMessageBuilder)
    {
        $this->animalFlagSoapMessageBuilder = $animalFlagSoapMessageBuilder;
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
            throw new PreconditionFailedHttpException($this->translateUcFirstLower("JSON BODY MUST HAVE THE TREATMENT STRUCTURE"));
        }

        $treatment
            ->setType($type)
            ->setLocation($location)
            ->removeEndDateIfEqualToStartDate()
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

        /** @var ArrayCollection|Animal[] $existingAnimals */
        $existingAnimals = new ArrayCollection();

        /** @var Animal $animal */
        foreach ($treatment->getAnimals() as $animal) {
            $animalId = $animal->getId();

            /** @var Animal $existingAnimal */
            $existingAnimal = $em->getRepository(Animal::class)->find($animalId);

            if ($existingAnimal !== null || in_array($animalId, $historicAnimalsIds)) {
                $existingAnimals->add($existingAnimal);
            } else {
                throw new PreconditionFailedHttpException($this->translator->trans("ANIMAL_NOT_FOUND", ['animal_id' => $animalId]));
            }
        }

        if ($treatmentTemplate instanceof QFever) {
            $this->validateQFeverFlagsForAllAnimals($treatmentTemplate, $existingAnimals);
        }

        // No duplicates are being created, so what is being meant with "duplicates"?
        //TODO check for duplicates

        // First complete all the validation before sending any messages to RVO!

        $medicationSelections = new ArrayCollection();

        $medications = $treatment->getTreatmentTemplate()->getMedications();

        if (!$treatmentTemplate->isEditable()) {
            $medications = $treatmentTemplate->getMedications();
        }

        /** @var TreatmentMedication $treatmentMedication */
        foreach ($medications as $treatmentMedication) {
            $treatmentMedicationInDB = $treatmentMedication;

            if ($treatmentTemplate->isEditable()) {
                /** @var TreatmentMedication $treatmentMedicationInDB */
                $treatmentMedicationInDB = $this->getManager()
                    ->getRepository(TreatmentMedication::class)->find($treatmentMedication->getId());

                if (!$treatmentMedicationInDB) {
                    throw new PreconditionFailedHttpException($this->translator->trans('MEDICATION_NOT_FOUND', ['medication_id' => $treatmentMedication->getId()]));
                }
            }

            $medicationSelection = new MedicationSelection();

            $medicationSelection
                ->setTreatment($treatment)
                ->setTreatmentMedication($treatmentMedicationInDB);

            $treatmentDuration = $treatmentMedicationInDB->getTreatmentDuration();

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
        }

        $treatment->__construct();

        $treatment
            ->setMedicationSelections($medicationSelections)
            ->setCreationBy($this->getUser())
            ->setAnimals($existingAnimals)
            ->setTreatmentTemplate($treatmentTemplate);

        $this->validateTreatmentDuration($treatment);


        foreach ($medicationSelections as $medicationSelection) {
            $em->persist($medicationSelection);
        }

        $em->persist($treatment);

        if ($treatmentTemplate instanceof QFever) {
            if ($location->isDutchLocation()) {
                $this->createQFeverByRvoPostRequest($treatment, $treatmentTemplate, $existingAnimals, $client, $loggedInUser);
            } else {
                $this->createCompletedQFeverMessage($treatment, $existingAnimals, $loggedInUser);
            }
        }

        ActionLogWriter::createTreatment($em, $request, $loggedInUser, $treatment);
        $output = $this->getBaseSerializer()->getDecodedJson($treatment, $this->getJmsGroupByQueryForTreatment($request));
        return ResultUtil::successResult($output);
    }


    private function createQFeverByRvoPostRequest(
        Treatment $treatment,
        QFever $qFeverTemplate,
        ArrayCollection $existingAnimals,
        Client $client,
        Person $loggedInUser
    )
    {
        $isQFeverTreatment = $this->qFeverService->isQFeverDescription($treatment->getDescription());
        if (!$isQFeverTreatment) {
            return;
        }

        $declareAnimalFlags = [];

        try {

            // The treatment has to be persisted first before being able to persist DeclareAnimalFlag
            $flagType = QFeverService::getFlagType($qFeverTemplate->getDescription(), $qFeverTemplate->getAnimalType());

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

                $declareAnimalFlag = $this->animalFlagMessageBuilder->buildMessage($declareAnimalFlag, $client,
                    $loggedInUser, $treatment->getLocation());

                $this->getManager()->persist($declareAnimalFlag);

                $declareAnimalFlags[$declareAnimalFlag->getRequestId()] = [
                    'xml' => $this->animalFlagSoapMessageBuilder->parseSoapXmlRequestBody($declareAnimalFlag),
                    'declare' => $declareAnimalFlag,
                ];
            }

            $this->getManager()->flush();


        } catch (\Exception $exception) {
            if ($exception instanceof UniqueConstraintViolationException) {

                $this->resetManager();

                SqlUtil::bumpPrimaryKeySeq($this->getConnection(), DeclareBase::getTableName());
            }
            throw $exception;
        }


        foreach ($declareAnimalFlags as $requestId => $set) {
            $soapMessage = $set['xml'];
            /** @var DeclareAnimalFlag $declareAnimalFlag */
            $declareAnimalFlag = $set['declare'];

            try {
                $this->sendRawMessageToQueue($soapMessage, RequestType::DECLARE_ANIMAL_FLAG, $requestId);
            } catch (\Exception $exception) {
                $declareAnimalFlag->setFailedRequestState();
                $declareAnimalFlag->setFailedValues(
                    'Het versturen van de meldingen naar RVO is mislukt',
                    NsfoErrorCode::FAILED_SENDING_TO_RVO
                );
                $this->getManager()->persist($declareAnimalFlag);
                $this->getManager()->flush();
            }
        }
    }


    private function createCompletedQFeverMessage(
        Treatment $treatment,
        ArrayCollection $existingAnimals,
        Person $loggedInUser
    )
    {
        try {

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
                $declareAnimalFlag
                    ->setResponseLogDate(new DateTime())
                    ->setSuccessValues()
                ;

                $em->persist($declareAnimalFlag);
            }

            $em->flush();

        } catch (\Exception $exception) {
            if ($exception instanceof UniqueConstraintViolationException) {

                $this->resetManager();

                SqlUtil::bumpPrimaryKeySeq($this->getConnection(), DeclareBase::getTableName());
            }
            throw $exception;
        }
    }


    private function validateQFeverFlagsForAllAnimals(
        TreatmentTemplate $template,
        ArrayCollection $existingAnimals
    )
    {
        $flagType = QFeverService::getFlagType($template->getDescription(), $template->getAnimalType());
        $animalIds = array_map(function (Animal $animal) {
            return $animal->getId();
        }, $existingAnimals->toArray());

        $ulnForExistingFlags = $this->getManager()->getRepository(DeclareAnimalFlag::class)
            ->getUlnsForExistingFlags($flagType, $animalIds);

        if (!empty($ulnForExistingFlags)) {
            $errorMessage = $this->translator->trans('q-fever.duplicate.flag', [
                '%description%' => $template->getDescription(),
                '%flag%' => $flagType
            ]).': '.implode(',',$ulnForExistingFlags);
            throw new BadRequestHttpException($errorMessage);
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
            throw new PreconditionFailedHttpException($this->translateUcFirstLower("NO TREATMENT TEMPLATE FOUND WITH ID").": ". $treatmentTemplateId);
        }

        $description = $treatment->getDescription();
        if ($description === null) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower("DESCRIPTION IS MISSING"));
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

    private function validateTreatmentDuration(Treatment $treatment)
    {
        if ($treatment->getTreatmentTemplate() instanceof QFever) {
            // For QFever no endDate can be used.
            return;
        }

        $durationInDays = $treatment->getEndDate() ? TimeUtil::getAgeInDays($treatment->getStartDate(), $treatment->getEndDate()) : 0;

        $minTreatmentDuration = 0;

        foreach ($treatment->getMedicationSelections() as $medicationSelection) {
            $treatmentDuration = $medicationSelection->getTreatmentMedication()->getTreatmentDuration();
            $minTreatmentDuration = $minTreatmentDuration < $treatmentDuration ? $treatmentDuration : $minTreatmentDuration;
        }

        if ($durationInDays < $minTreatmentDuration) {
            $minEndTime = clone $treatment->getStartDate();
            $minEndTime->add(new DateInterval('P'.ceil($minTreatmentDuration).'D'));

            throw new BadRequestHttpException($this->translator->trans('treatment.duration.too-short', [
                '%inputDurationDays%' => $durationInDays,
                '%treatmentDurationDays%' => $minTreatmentDuration,
                '%inputStartDate%' => $treatment->getStartDate()->format(DateUtil::DATE_USER_DISPLAY_FORMAT),
                '%minEndDate%' => $minEndTime->format(DateUtil::DATE_USER_DISPLAY_FORMAT),
            ]));
        }
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
            throw new PreconditionFailedHttpException($this->translateUcFirstLower("THIS TREATMENT DOES NOT BELONG TO THE LOCATION WITH UBN").": ". $location->getUbn());
        }

        $content = RequestUtil::getContentAsArrayCollection($request);

        $startDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::START_DATE, $content);
        $endDate = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::END_DATE, $content);

        $treatment
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->removeEndDateIfEqualToStartDate()
        ;

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
}
