<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTemplateAPIControllerInterface;
use AppBundle\Entity\MedicationOption;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Entity\TreatmentTemplateRepository;
use AppBundle\Entity\TreatmentType;
use AppBundle\Entity\TreatmentTypeRepository;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\TreatmentTypeOption;
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
class TreatmentTemplateService extends ControllerServiceBase implements TreatmentTemplateAPIControllerInterface
{
    /** @var TreatmentTemplateRepository */
    private $treatmentTemplateRepository;

    /** @var TreatmentTypeRepository */
    private $treatmentTypeRepository;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);

        $this->treatmentTemplateRepository = $this->em->getRepository(TreatmentTemplate::class);
        $this->treatmentTypeRepository = $this->em->getRepository(TreatmentType::class);
    }


    /**
     * @param Request $request
     * @return array
     */
    private function getJmsGroupByQuery(Request $request)
    {
        if(RequestUtil::getBooleanQuery($request,QueryParameter::MINIMAL_OUTPUT,true)) {
            return [JmsGroup::TREATMENT_TEMPLATE_MIN];
        }
        return [JmsGroup::TREATMENT_TEMPLATE];
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
        //TODO add validation that clients can only see their own template lists. Employees are still allowed to see all.

        $location = $this->getLocationByUbn($ubn);
        if ($location instanceof JsonResponse) { return $location; }

        $templates = $this->treatmentTemplateRepository->findActiveIndividualTypeByLocation($location);
        $output = $this->serializer->getDecodedJson($templates, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }


    /**
     * @param string|int $ubn
     * @return JsonResponse|\AppBundle\Entity\Location|null
     */
    private function getLocationByUbn($ubn)
    {
        if (!ctype_digit($ubn) && !is_int($ubn)) {
            return Validator::createJsonResponse('UBN must be a number', 428);
        }

        $location = $this->locationRepository->findOneByActiveUbn($ubn);
        if ($location === null) {
            return Validator::createJsonResponse('No active location found for given UBN', 428);
        }
        return $location;
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
        //TODO add validation that clients can only see their own template lists. Employees are still allowed to see all.

        $location = $this->getLocationByUbn($ubn);
        if ($location instanceof JsonResponse) { return $location; }

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
        if($this->userService->getEmployee() === null) { return AdminValidator::getStandardErrorResponse(); }

        /** @var TreatmentTemplate $template */
        $template = $this->serializer->deserializeToObject($request->getContent(), TreatmentTemplate::class);
        if (!($template instanceof TreatmentTemplate)) {
            return Validator::createJsonResponse('Json body must have the TreatmentTemplate structure', 428);
        }
        $template->setType($type);

        //Validation
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
        


        /** @var MedicationOption $medication */
        foreach ($template->getMedications() as $medication)
        {
            $medication->setTreatmentTemplate($template);
        }

        $template->__construct();
        $template
            ->setLocation($location)
            ->setTreatmentType($treatmentType)
            ->setCreationBy($this->userService->getUser())
        ;

        $this->em->persist($template);
        $this->em->flush();

        //TODO ActionLog

        $output = $this->serializer->getDecodedJson($template, $this->getJmsGroupByQuery($request));

        return ResultUtil::successResult($output);
    }


    /**
     * @param $medications
     * @return JsonResponse|bool
     */
    private function hasDuplicateMedicationDescriptions($medications)
    {
        $descriptions = [];
        $duplicateDescriptions = [];
        /** @var MedicationOption $medication */
        foreach ($medications as $medication)
        {
            $description = $medication->getDescription();
            if (in_array($description, $descriptions)) {
                $duplicateDescriptions[] = $description;
            } else {
                $descriptions[] = $description;
            }
        }

        if (count($duplicateDescriptions) > 0) {
            return Validator::createJsonResponse('Een medicijn mag alleen 1x in de medicijnen lijst voorkomen', 428);
        }
        return false;
    }


    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function editIndividualTemplate(Request $request, $templateId)
    {
        // TODO: Implement editIndividualTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function editLocationTemplate(Request $request, $templateId)
    {
        // TODO: Implement editLocationTemplate() method.

        return ResultUtil::successResult('ok');
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
        if($this->userService->getEmployee() === null) { return AdminValidator::getStandardErrorResponse(); }

        if (!ctype_digit($templateId) && !is_int($templateId)) {
            return Validator::createJsonResponse('TemplateId must be an integer', 428);
        }

        $type = TreatmentTypeService::getValidateType($type);
        if ($type instanceof JsonResponse) { return $type; }

        $template = $this->treatmentTemplateRepository->findOneBy(['type' => $type, 'id' => $templateId]);
        if ($template === null) {
            return Validator::createJsonResponse('No template of type '.$type
                .' found for id '.$templateId, 428);
        }

        if ($template->isActive() === false) {
            return Validator::createJsonResponse('Template has already been deactivated', 428);
        }

        $template->setIsActive(false);
        $this->em->persist($template);
        $this->em->flush();

        //TODO ActionLog

        $output = $this->serializer->getDecodedJson($template, $this->getJmsGroupByQuery($request));
        return ResultUtil::successResult($output);
    }
}