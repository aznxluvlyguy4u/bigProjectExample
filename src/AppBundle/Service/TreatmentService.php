<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\MedicationOption;
use AppBundle\Entity\MedicationSelection;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Class TreatmentService
 * @package AppBundle\Service
 */
class TreatmentService extends TreatmentServiceBase implements TreatmentAPIControllerInterface
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getIndividualTreatments(Request $request)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getLocationTreatments(Request $request)
    {
        return ResultUtil::successResult('ok');
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
        ;

        //Validation
        $treatment = $this->baseValidateDeserializedTreatment($treatment);
        if ($treatment instanceof JsonResponse) { return $treatment; }

        /** @var ArrayCollection<Animal> $treatmentAnimals */
        $treatmentAnimals = $treatment->getAnimals();

        /** @var ArrayCollection<Animal> $existingAnimals */
        $existingAnimals = new ArrayCollection();

        /** @var Animal $animal */
        foreach ($treatmentAnimals as $animal) {
            $animalId = $animal->getId();
            $existingAnimal = $em->getRepository(Animal::class)->find($animalId);
            if ($existingAnimal !== null) {
                $existingAnimals->add($existingAnimal);
            } else {
                throw new PreconditionFailedHttpException("Animal with id ".$animalId." not found");
            }
        }

        $medicationSelections = $treatment->getMedicationSelections();

        //TODO check for duplicates

        /** @var MedicationSelection $medicationSelection */
        foreach ($medicationSelections as $medicationSelection)
        {

            if ($medicationSelection->getWaitingDays() === null) {
                throw new PreconditionFailedHttpException("No waiting days have been filled in.");
            }

            $medicationSelectionName = $medicationSelection->getTreatmentMedication()->getName();

            /** @var TreatmentMedication $treatmentMedication */
            $treatmentMedication = $this->treatmentMedicationRepository->findOneBy(['name' => $medicationSelectionName]);

            $medicationSelection->setTreatmentMedication($treatmentMedication);
        }

        $treatmentTemplateId = $treatment->getTreatmentTemplate()->getId();

        /** @var TreatmentTemplate $treatmentTemplate */
        $treatmentTemplate = $this->treatmentTemplateRepository->find($treatmentTemplateId);

        $treatment->__construct();
        $treatment
            ->setCreationBy($this->getUser())
            ->setAnimals($existingAnimals)
            ->setTreatmentTemplate($treatmentTemplate);

        $em->persist($treatment);
        /** @var MedicationSelection $medicationSelection */
        foreach ($treatment->getMedicationSelections() as $medicationSelection) {
            $medicationSelection->setTreatment($treatment);
            $em->persist($medicationSelection);
        }
        $em->flush();

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

        $treatmentTemplate = $this->treatmentTemplateRepository->find($treatmentTemplateId);

        if ($treatmentTemplate === null) {
            throw new PreconditionFailedHttpException("No treatment template found with id: ". $treatmentTemplateId);
        }

        $description = $treatment->getDescription();
        if ($description === null) {
            throw new PreconditionFailedHttpException("Description is missing");
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
     * @param $treatmentId
     * @return JsonResponse
     */
    function editIndividualTreatment(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $treatmentId
     * @return JsonResponse
     */
    function editLocationTreatment(Request $request, $treatmentId)
    {
        return ResultUtil::successResult('ok');
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


}