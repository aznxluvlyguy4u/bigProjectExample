<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\TreatmentAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\MedicationOption;
use AppBundle\Entity\MedicationSelection;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use DateInterval;
use DateTime;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Validator\Constraints\Date;

/**
 * Class TreatmentService
 * @package AppBundle\Service
 */
class TreatmentService extends TreatmentServiceBase implements TreatmentAPIControllerInterface
{

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

        $historicAnimalsIds = new ArrayCollection();

        $historicAnimals = $em->getRepository(Animal::class)
            ->getHistoricLiveStock($location, $this->getCacheService(), $this->getBaseSerializer());

        /** @var Animal $historicAnimal */
        foreach ($historicAnimals as $historicAnimal) {
            $historicAnimalsIds->add($historicAnimal->getId());
        }

        /** @var ArrayCollection<Animal> $existingAnimals */
        $existingAnimals = new ArrayCollection();

        /** @var Animal $animal */
        foreach ($treatment->getAnimals() as $animal) {
            $animalId = $animal->getId();

            /** @var Animal $existingAnimal */
            $existingAnimal = $em->getRepository(Animal::class)->find($animalId);

            if ($existingAnimal !== null || in_array($animalId, $historicAnimalsIds->toArray())) {
                $existingAnimals->add($existingAnimal);
            } else {
                throw new PreconditionFailedHttpException("Animal with id ".$animalId." not found");
            }
        }

        // No duplicates are being created, so what is being meant with "duplicates"?
        //TODO check for duplicates

        /** @var TreatmentTemplate $treatmentTemplate */
        $treatmentTemplate = $em->getRepository(TreatmentTemplate::class)->find($treatment->getTreatmentTemplate()->getId());

        /** @var MedicationOption $medicationOption */
        foreach ($treatmentTemplate->getMedications() as $medicationOption)
        {
            $treatmentDuration = $medicationOption->getTreatmentDuration();
            $medicationSelection = new MedicationSelection();

            $medicationSelection
                ->setTreatment($treatment)
                ->setMedicationOption($medicationOption)
            ;

            if ($treatmentDuration !== 'eenmalig') {
                $roundedTreatmentDuration = round($treatmentDuration, 0, PHP_ROUND_HALF_UP);

                // Subtract 1 to account for the start day of the treatment.
                $correctedTreatmentDuration = $roundedTreatmentDuration-1;

                $daysToAdd = $correctedTreatmentDuration + $medicationOption->getWaitingDays();

                $treatmentStartDate = clone $treatment->getStartDate();
                if ($daysToAdd > 0) {
                    $treatmentStartDate->add(new DateInterval('P'.$daysToAdd.'D'));
                }

                $medicationSelection
                    ->setWaitingTimeEnd($treatmentStartDate);
            } else {
                $treatmentStartDate = clone $treatment->getStartDate();
                $medicationSelection
                    ->setWaitingTimeEnd($treatmentStartDate->add(new DateInterval('P'.$medicationOption->getWaitingDays().'D')));
            }

            $em->persist($medicationSelection);
        }

        $treatment->__construct();

        $treatment
            ->setCreationBy($this->getUser())
            ->setAnimals($existingAnimals)
            ->setTreatmentTemplate($treatmentTemplate);

        $em->persist($treatment);
        try {
            $em->flush();
        } catch (Exception $e) {
            if ($e instanceof UniqueConstraintViolationException) {
               return ResultUtil::errorResult('A treatment already exists!', 500);
            }
        }


        ActionLogWriter::createTreatment($em, $request, $loggedInUser, $treatment);

        $output = $this->getBaseSerializer()->getDecodedJson($treatment, $this->getJmsGroupByQueryForTreatment($request));

        return ResultUtil::successResult($output);
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

        $page = $request->query->getInt('page', 1);
        $searchQuery = $request->get('query', '');

        $treatments = $this->getManager()->getRepository(Treatment::class)
            ->getHistoricTreatments($location->getUbn(), $page, 10, $searchQuery);

        return ResultUtil::successResult($treatments);
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
        $treatmentTemplateDescription = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::DESCRIPTION, $content);

        /** @var TreatmentTemplate $treatmentTemplate */
        $treatmentTemplate = $em->getRepository(TreatmentTemplate::class)->findOneBy(['description' => $treatmentTemplateDescription]);

        if ($treatmentTemplate === null) {
            throw new PreconditionFailedHttpException("No TreatmentTemplate was found with the description: ".$treatmentTemplateDescription);
        }

        $treatment
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setTreatmentTemplate($treatmentTemplate)
            ->setDescription($treatmentTemplate->getDescription());

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
