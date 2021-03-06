<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTemplateAPIControllerInterface;
use AppBundle\Entity\QFever;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Entity\TreatmentType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Class TreatmentTemplateService
 * @package AppBundle\Service
 */
class TreatmentTemplateService extends TreatmentServiceBase implements TreatmentTemplateAPIControllerInterface
{

    function getTemplates(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        if (!$location) {
            throw new BadRequestHttpException('Location is missing');
        }

        $activeOnly = RequestUtil::getBooleanQuery($request, QueryParameter::ACTIVE_ONLY, true);
        $templates = $this->treatmentTemplateRepository->findAllBelongingToLocation($location, $activeOnly);
        $output = $this->getBaseSerializer()->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getIndividualDefaultTemplates(Request $request)
    {
        $activeOnly = RequestUtil::getBooleanQuery($request, QueryParameter::ACTIVE_ONLY, true);
        $templates = $this->treatmentTemplateRepository->findIndividualTypeByLocation(null, $activeOnly);
        $output = $this->getBaseSerializer()->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

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

        $activeOnly = RequestUtil::getBooleanQuery($request, QueryParameter::ACTIVE_ONLY, true);
        $templates = $this->treatmentTemplateRepository->findIndividualTypeByLocation($location, $activeOnly);
        $output = $this->getBaseSerializer()->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getLocationDefaultTemplates(Request $request)
    {
        $activeOnly = RequestUtil::getBooleanQuery($request, QueryParameter::ACTIVE_ONLY, true);
        $templates = $this->treatmentTemplateRepository->findLocationTypeByLocation(null, $activeOnly);
        $output = $this->getBaseSerializer()->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

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

        $activeOnly = RequestUtil::getBooleanQuery($request, QueryParameter::ACTIVE_ONLY, true);
        $templates = $this->treatmentTemplateRepository->findLocationTypeByLocation($location, $activeOnly);

        $output = $this->getBaseSerializer()->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    function createIndividualTemplate(Request $request)
    {
        return $this->createTemplate($request, TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    function createLocationTemplate(Request $request)
    {
        return $this->createTemplate($request, TreatmentTypeOption::LOCATION);
    }

    /**
     * @param Request $request
     * @param $type
     * @return JsonResponse
     * @throws Exception|DBALException
     */
    private function createTemplate(Request $request, $type)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentTemplate $template */
        $template = $this->getBaseSerializer()->deserializeToObject($request->getContent(), TreatmentTemplate::class);

        if (!($template instanceof TreatmentTemplate)) {
            return Validator::createJsonResponse($this->translateUcFirstLower("JSON BODY MUST HAVE TREATMENT TEMPLATE STRUCTURE"), 428);
        }

        $template->setType($type);

        //Validation
        $template = $this->baseValidateDeserializedTreatmentTemplate($template);
        if ($template instanceof JsonResponse) { return $template; }

        $template->__construct();
        if ($template instanceof QFever) {
            if ($template->getLocation() !== null) {
                throw new PreconditionFailedHttpException($this->translateUcFirstLower("A Q-FEVER TEMPLATE CAN NOT BE CREATED FOR A LOCATION")."!");
            }
            $template
                ->setQFeverType(QFeverService::qFeverTypeLetter($template->getTreatmentType()->getDescription()))
                ->setIsEditable(false);
        }

        //TODO check for duplicates

        /** @var TreatmentMedication $medication */
        foreach ($template->getMedications() as $medication)
        {
            /** @var TreatmentMedication $treatmentMedication */
            $treatmentMedication = $this->treatmentMedicationRepository->findOneBy(['name' => $medication->getName()]);

            $template->removeMedication($medication);

            $template->addMedication($treatmentMedication);
        }

        $template->setCreationBy($this->getUser());

        $this->getManager()->persist($template);

        try {
            $this->getManager()->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new BadRequestHttpException('DUPLICATE DESCRIPTION.');
        }

        AdminActionLogWriter::createTreatmentTemplate($this->getManager(), $admin, $request, $template);

        $output = $this->getBaseSerializer()->getDecodedJson($template, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }


    /**
     * @param TreatmentTemplate $template
     * @param bool $ignoreTreatmentTypeValidation
     * @return JsonResponse|TreatmentTemplate
     * @throws Exception
     */
    private function baseValidateDeserializedTreatmentTemplate(TreatmentTemplate $template, bool $ignoreTreatmentTypeValidation = false)
    {
        $locationRequested = $template->getLocation();
        $location = null;
        if ($locationRequested) {
            $location = $this->getLocationByUbn($locationRequested->getUbn());
            if ($location instanceof JsonResponse) { return $location; }
        }

        $description = $template->getDescription();
        if ($description === null) {
            return Validator::createJsonResponse($this->translateUcFirstLower('DESCRIPTION IS MISSING'), 428);
        }

        $type = TreatmentTypeService::getValidateType($template->getType());
        if ($type instanceof JsonResponse) { return $type; }


        if (!$ignoreTreatmentTypeValidation) {
            $treatmentType = $this->treatmentTypeRepository->findActiveOneByTypeAndDescription($type, $description);
            if (!$treatmentType) {
                $descriptionFirstPart = ArrayUtil::firstValue(explode(' - UBN', $description));
                $treatmentType = $this->treatmentTypeRepository->findActiveOneByTypeAndDescription($type,
                    $descriptionFirstPart);
            }

            if (!$treatmentType) {
                if ($type === TreatmentTypeOption::INDIVIDUAL) {
                    $treatmentType = $this->treatmentTypeRepository->findOpenDescriptionType();
                } else {
                    //TreatmentTypeOption::LOCATION
                    return Validator::createJsonResponse(
                        $this->translateUcFirstLower("TREATMENT DESCRIPTION DOES NOT EXIST IN DATABASE FOR ACTIVE TREATMENT TYPE WITH TYPE LOCATION"),
                        428);
                }
            }
            $template->setTreatmentType($treatmentType);
        }

        $template->setLocation($location);

        return $template;
    }


    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     * @throws Exception
     */
    function editIndividualTemplate(Request $request, $templateId)
    {
        return $this->editTemplate($request, $templateId, TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     * @throws Exception
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
     * @throws Exception
     */
    private function editTemplate(Request $request, $templateId, $type)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        //existing template data
        /** @var TreatmentTemplate $templateInDatabase */
        $templateInDatabase = $this->getTemplateByIdAndType($templateId, $type);
        if ($templateInDatabase instanceof JsonResponse) { return $templateInDatabase; }

        $oldTemplateMedications = $templateInDatabase->getMedications();

        //new template data
        /** @var TreatmentTemplate $template */
        $template = $this->getBaseSerializer()->deserializeToObject($request->getContent(), TreatmentTemplate::class);
        if (!($template instanceof TreatmentTemplate)) {
            return Validator::createJsonResponse($this->translateUcFirstLower("JSON BODY MUST HAVE TREATMENT TEMPLATE STRUCTURE"), 428);
        }

        /* Validation */
        if ($template->getType() !== null && $template->getType() !== $type) {
            //Prevent unpredictable results by blocking the editing of the type.
            return Validator::createJsonResponse('Template type may not be edited!', 428);
        }

        if (
            !$templateInDatabase->isEditable() &&
            $templateInDatabase->getDescription() !== $template->getDescription()
        ) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower("THIS TEMPLATE IS NOT EDITABLE"));
        }

        $template->setType($type); //Necessary for baseValidation

        $template = $this->baseValidateDeserializedTreatmentTemplate($template, true);

        if ($template instanceof JsonResponse) { return $template; }

        $newTreatmentMedications = new ArrayCollection();

        /** @var TreatmentMedication $medication */
        foreach ($template->getMedications() as $medication) {
            /** @var TreatmentMedication $treatmentMedication */
            $treatmentMedication = $this->treatmentMedicationRepository->findOneBy(['name' => $medication->getName()]);

            if ($treatmentMedication->getWaitingDays() === null) {
                throw new PreconditionFailedHttpException($this->translateUcFirstLower("NO WAITING DAYS HAVE BEEN FILLED IN"));
            }

//            $templateInDatabase->removeMedication($medication);

            $newTreatmentMedications->add($treatmentMedication);
        }


        $templateInDatabase->setMedications($newTreatmentMedications);


        //TODO check if the todo beneath is done
        //TODO check for duplicates

        /* Update */
        $isAnyValueUpdated = false;
        $this->actionLogDescription = '';

        if ($oldTemplateMedications !== $templateInDatabase->getMedications()) {
            $isAnyValueUpdated = true;
        }

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
        /** @var TreatmentType $currentTreatmentType */
        $currentTreatmentType = $templateInDatabase->getTreatmentType();
        if ($template->getTreatmentType() != null && $template->getTreatmentType()->getId() != null &&
            $currentTreatmentType->getId() !== $template->getTreatmentType()->getId()) {
            $templateInDatabase->setTreatmentType($template->getTreatmentType());
            $isAnyValueUpdated = true;
        }

        //Update description
        if ($templateInDatabase->getDescription() !== $template->getDescription()) {
            $this->appendUpdateDescription($templateInDatabase->getDescription(), $template->getDescription());
            $templateInDatabase->setDescription($template->getDescription());
            $isAnyValueUpdated = true;
        }

        if ($isAnyValueUpdated) {
            $templateInDatabase
                ->setEditedBy($this->getUser())
                ->setLogDate(new DateTime());

            $this->getManager()->persist($templateInDatabase);
            $this->getManager()->flush();

            AdminActionLogWriter::editTreatmentTemplate($this->getManager(), $template->getLocationOwner(), $admin, $this->actionLogDescription);
        }

        $output = $this->getBaseSerializer()->getDecodedJson($templateInDatabase, $this->getJmsGroupByQuery($request));

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
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        $template = $this->getTemplateByIdAndType($templateId, $type);
        if ($template instanceof JsonResponse) { return $template; }

        if (!$template->isEditable()) {
            throw new PreconditionFailedHttpException($this->translateUcFirstLower("THIS TEMPLATE IS NOT EDITABLE"));
        }

        $template->setIsActive(false);
        $this->getManager()->persist($template);
        $this->getManager()->flush();

        AdminActionLogWriter::deleteTreatmentTemplate($this->getManager(), $template->getLocationOwner(), $admin, $template);

        $output = $this->getBaseSerializer()->getDecodedJson($template, $this->getJmsGroupByQuery($request));
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function reactivateIndividualTemplate(Request $request, $templateId)
    {
        return $this->reactivateTemplate($request, $templateId,TreatmentTypeOption::INDIVIDUAL);
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function reactivateLocationTemplate(Request $request, $templateId)
    {
        return $this->reactivateTemplate($request, $templateId, TreatmentTypeOption::LOCATION);
    }


    /**
     * @param $request
     * @param $templateId
     * @param $type
     * @return JsonResponse
     */
    private function reactivateTemplate($request, $templateId, $type)
    {
        $admin = $this->getEmployee();
        if($admin === null) { return AdminValidator::getStandardErrorResponse(); }

        $template = $this->getTemplateByIdAndType($templateId, $type, true);
        if ($template instanceof JsonResponse) { return $template; }

        $template->setIsActive(true);
        $this->getManager()->persist($template);
        $this->getManager()->flush();

        AdminActionLogWriter::reactivateTreatmentTemplate($this->getManager(), $template->getLocationOwner(), $admin, $template);

        $output = $this->getBaseSerializer()->getDecodedJson($template, $this->getJmsGroupByQuery($request));
        return ResultUtil::successResult($output);
    }

    /**
     * @return JsonResponse
     */
    public function getQFeverDescriptions()
    {
        $descriptions = $this->getManager()->getRepository(QFever::class)
            ->findQFeverDescriptions();

        return ResultUtil::successResult($descriptions);
    }
}
