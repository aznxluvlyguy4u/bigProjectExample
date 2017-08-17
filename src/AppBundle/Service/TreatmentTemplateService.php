<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTemplateAPIControllerInterface;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\MedicationOption;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Entity\TreatmentTemplateRepository;
use AppBundle\Entity\TreatmentType;
use AppBundle\Entity\TreatmentTypeRepository;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TreatmentTemplateService
 * @package AppBundle\Service
 */
class TreatmentTemplateService extends TreatmentServiceBase implements TreatmentTemplateAPIControllerInterface
{

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getIndividualDefaultTemplates(Request $request)
    {
        $templates = $this->treatmentTemplateRepository->findActiveIndividualTypeByLocation(null);
        $output = $this->serializer->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @param $ubn
     * @return JsonResponse
     */
    function getIndividualSpecificTemplates(Request $request, $ubn)
    {
        $location = $this->getLocationByUbn($ubn);
        if ($location instanceof JsonResponse) { return $location; }

        $clientValidation = $this->validateIfLocationBelongsToClient($location);
        if ($clientValidation instanceof JsonResponse) { return $clientValidation; }

        $templates = $this->treatmentTemplateRepository->findActiveIndividualTypeByLocation($location);
        $output = $this->serializer->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getLocationDefaultTemplates(Request $request)
    {
        $templates = $this->treatmentTemplateRepository->findActiveLocationTypeByLocation(null);
        $output = $this->serializer->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @param $ubn
     * @return JsonResponse
     */
    function getLocationSpecificTemplates(Request $request, $ubn)
    {
        $location = $this->getLocationByUbn($ubn);
        if ($location instanceof JsonResponse) { return $location; }

        $clientValidation = $this->validateIfLocationBelongsToClient($location);
        if ($clientValidation instanceof JsonResponse) { return $clientValidation; }

        $templates = $this->treatmentTemplateRepository->findActiveLocationTypeByLocation($location);
        $output = $this->serializer->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createIndividualTemplate(Request $request)
    {
        return $this->createTemplate($request, TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createLocationTemplate(Request $request)
    {
        return $this->createTemplate($request, TreatmentTypeOption::LOCATION);
    }

    /**
     * @param Request $request
     * @param $type
     * @return JsonResponse
     */
    private function createTemplate(Request $request, $type)
    {
        $admin = $this->userService->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentTemplate $template */
        $template = $this->serializer->deserializeToObject($request->getContent(), TreatmentTemplate::class);
        if (!($template instanceof TreatmentTemplate)) {
            return Validator::createJsonResponse('Json body must have the TreatmentTemplate structure', 428);
        }
        $template->setType($type);

        //Validation
        $template = $this->baseValidateDeserializedTreatmentTemplate($template);
        if ($template instanceof JsonResponse) { return $template; }

        /** @var MedicationOption $medication */
        foreach ($template->getMedications() as $medication)
        {
            $medication->setTreatmentTemplate($template);
        }

        $template->__construct();
        $template->setCreationBy($this->userService->getUser());

        $this->em->persist($template);
        $this->em->flush();

        AdminActionLogWriter::createTreatmentTemplate($this->em, $admin, $request, $template);

        $output = $this->serializer->getDecodedJson($template, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }


    /**
     * @param TreatmentTemplate $template
     * @return JsonResponse|TreatmentTemplate
     */
    private function baseValidateDeserializedTreatmentTemplate(TreatmentTemplate $template)
    {
        $locationRequested = $template->getLocation();
        $location = null;
        if ($locationRequested) {
            $location = $this->getLocationByUbn($locationRequested->getUbn());
            if ($location instanceof JsonResponse) { return $location; }
        }

        $description = $template->getDescription();
        if ($description === null) {
            return Validator::createJsonResponse('Description is missing', 428);
        }

        $type = TreatmentTypeService::getValidateType($template->getType());
        if ($type instanceof JsonResponse) { return $type; }


        $treatmentType = $this->treatmentTypeRepository->findActiveOneByTypeAndDescription($type, $description);

        if (!$treatmentType) {
            if ($type === TreatmentTypeOption::INDIVIDUAL) {
                $treatmentType = $this->treatmentTypeRepository->findOpenDescriptionType();
            } else {
                //TreatmentTypeOption::LOCATION
                return Validator::createJsonResponse(
                    'Treatment description does not exist in database for active TreatmentType with type = LOCATION', 428);
            }
        }

        $medicationValidation = $this->hasDuplicateMedicationDescriptions($template->getMedications());
        if ($medicationValidation instanceof JsonResponse) { return $medicationValidation; }

        $template
            ->setLocation($location)
            ->setTreatmentType($treatmentType)
            ;

        return $template;
    }


    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function editIndividualTemplate(Request $request, $templateId)
    {
        return $this->editTemplate($request, $templateId, TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function editLocationTemplate(Request $request, $templateId)
    {
        return $this->editTemplate($request, $templateId, TreatmentTypeOption::LOCATION);
    }


    /**
     * @param Request $request
     * @param int $templateId
     * @param $type
     * @return JsonResponse
     */
    private function editTemplate(Request $request, $templateId, $type)
    {
        $admin = $this->userService->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        $templateInDatabase = $this->getTemplateByIdAndType($templateId, $type);
        if ($templateInDatabase instanceof JsonResponse) { return $templateInDatabase; }

        /** @var TreatmentTemplate $template */
        $template = $this->serializer->deserializeToObject($request->getContent(), TreatmentTemplate::class);
        if (!($template instanceof TreatmentTemplate)) {
            return Validator::createJsonResponse('Json body must have the TreatmentTemplate structure', 428);
        }

        /* Validation */

        if ($template->getType() !== null && $template->getType() !== $type) {
            //Prevent unpredictable results by blocking the editing of the type.
            return Validator::createJsonResponse('Template type may not be edited!', 428);
        }

        $template->setType($type); //Necessary for baseValidation

        $template = $this->baseValidateDeserializedTreatmentTemplate($template);
        if ($template instanceof JsonResponse) { return $template; }


        /* Update */

        $isAnyValueUpdated = false;
        $this->actionLogDescription = '';

        //Update Location
        $currentLocation = $templateInDatabase->getLocation();
        $location = $template->getLocation();

        $updateLocation = false;
        if ($currentLocation !== null && $location !== null) {
            if ($currentLocation->getId() !== $location->getId()) {
                $this->appendUpdateDescription($currentLocation->getUbn(), $location->getUbn());
                $updateLocation = true;
            }

        } elseif ($currentLocation === null && $location !== null) {
            $this->appendUpdateDescription('', $location->getUbn());
            $updateLocation = true;

        } elseif ($currentLocation !== null && $location === null) {
            $this->appendUpdateDescription($currentLocation->getUbn(), '');
            $updateLocation = true;
        }

        if ($updateLocation) {
            $templateInDatabase->setLocation($location);
            $isAnyValueUpdated = true;
        }

        //Update TreatmentType
        $currentTreatmentType = $templateInDatabase->getTreatmentType();
        if ($currentTreatmentType->getId() !== $template->getTreatmentType()->getId()) {
            $templateInDatabase->setTreatmentType($template->getTreatmentType());
            $isAnyValueUpdated = true;
        }

        //Update description
        if ($templateInDatabase->getDescription() !== $template->getDescription()) {
            $this->appendUpdateDescription($templateInDatabase->getDescription(), $template->getDescription());
            $templateInDatabase->setDescription($template->getDescription());
            $isAnyValueUpdated = true;
        }

        //Update medications
        $newMedicationDosagesByDescription = [];
        /** @var MedicationOption $medication */
        foreach ($template->getMedications() as $medication)
        {
            $newMedicationDosagesByDescription[$medication->getDescription()] = $medication->getDosage();
        }

        $currentMedicationByDescription = [];
        foreach ($templateInDatabase->getMedications() as $medication)
        {
            $currentMedicationByDescription[$medication->getDescription()] = $medication;

            if (key_exists($medication->getDescription(), $newMedicationDosagesByDescription)) {
                $newMedicationDosage = $newMedicationDosagesByDescription[$medication->getDescription()];
                if ($medication->getDosage() !== $newMedicationDosage) {
                    //Update dosage
                    $this->appendDescription($medication->getDescription(). ' dosage => '.$newMedicationDosage);
                    $medication->setDosage($newMedicationDosage);
                    $isAnyValueUpdated = true;
                }
            } else {
                //Remove medication
                $this->appendDescription('remove '.$medication->getDescription());
                $templateInDatabase->removeMedication($medication);
                $this->em->remove($medication);
                $isAnyValueUpdated = true;
            }
        }

        /** @var MedicationOption $newMedication */
        foreach ($template->getMedications() as $newMedication)
        {
            if (!key_exists($newMedication->getDescription(), $currentMedicationByDescription)) {
                $templateInDatabase->addMedication($newMedication);
                $newMedication->setTreatmentTemplate($templateInDatabase);
                $this->appendDescription('add '.$newMedication->getDescription().'['.$newMedication->getDosage().']');
                $isAnyValueUpdated = true;
            }
        }


        if ($isAnyValueUpdated) {
            $templateInDatabase
                ->setEditedBy($this->userService->getUser())
                ->setLogDate(new \DateTime())
            ;
            $this->em->persist($templateInDatabase);
            $this->em->flush();

            AdminActionLogWriter::editTreatmentTemplate($this->em, $template->getLocationOwner(), $admin, $this->actionLogDescription);
        }

        $output = $this->serializer->getDecodedJson($templateInDatabase, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function deleteIndividualTemplate(Request $request, $templateId)
    {
        return $this->deleteTemplate($request, $templateId,TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function deleteLocationTemplate(Request $request, $templateId)
    {
        return $this->deleteTemplate($request, $templateId, TreatmentTypeOption::LOCATION);
    }

    /**
     * @param Request $request
     * @param int $templateId
     * @param string $type
     * @return JsonResponse
     */
    private function deleteTemplate($request, $templateId, $type)
    {
        $admin = $this->userService->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        $template = $this->getTemplateByIdAndType($templateId, $type);
        if ($template instanceof JsonResponse) { return $template; }

        $template->setIsActive(false);
        $this->em->persist($template);
        $this->em->flush();

        AdminActionLogWriter::deleteTreatmentTemplate($this->em, $template->getLocationOwner(), $admin, $template);

        $output = $this->serializer->getDecodedJson($template, $this->getJmsGroupByQuery($request));
        return ResultUtil::successResult($output);
    }
}