<?php

namespace AppBundle\Service;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\TreatmentTemplateAPIControllerInterface;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Entity\TreatmentTemplateRepository;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\TreatmentTypeOption;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
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

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                CacheService $cacheService, UserService $userService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);

        $this->treatmentTemplateRepository = $this->em->getRepository(TreatmentTemplate::class);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function getIndividualDefaultTemplates(Request $request)
    {
        $templates = $this->treatmentTemplateRepository->findActiveIndividualTypeByLocation(null);
        $output = $this->serializer->getDecodedJson($templates, [JmsGroup::TREATMENT_TEMPLATE]);

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

        $templates = $this->treatmentTemplateRepository->findActiveIndividualTypeByLocation($location);
        $output = $this->serializer->getDecodedJson($templates, [JmsGroup::TREATMENT_TEMPLATE]);

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
        $output = $this->serializer->getDecodedJson($templates, [JmsGroup::TREATMENT_TEMPLATE]);

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

        $templates = $this->treatmentTemplateRepository->findActiveLocationTypeByLocation($location);
        $output = $this->serializer->getDecodedJson($templates, [JmsGroup::TREATMENT_TEMPLATE]);

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createIndividualTemplate(Request $request)
    {
        /** @var TreatmentTemplate $template */
        $template = $this->serializer->deserializeToObject($request->getContent(), TreatmentTemplate::class);

        $locationRequested = $template->getLocation();
        $location = null;
        if ($locationRequested) {
            $location = $this->getLocationByUbn($locationRequested->getUbn());
        }

        $treatmentType = $template->getTreatmentType();
        if ($treatmentType === null) {
            return Validator::createJsonResponse('TreatmentType is missing', 428);
        }

        //TODO process POST
        //$treatmentType->getDescription();

        $output = $this->serializer->getDecodedJson($template, [JmsGroup::TREATMENT_TEMPLATE]);

        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createLocationTemplate(Request $request)
    {
        // TODO: Implement createLocationTemplate() method.

        return ResultUtil::successResult('ok');
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
        // TODO: Implement deleteIndividualTemplate() method.

        return ResultUtil::successResult('ok');
    }

    /**
     * @param Request $request
     * @param $templateId
     * @return JsonResponse
     */
    function deleteLocationTemplate(Request $request, $templateId)
    {
        // TODO: Implement deleteLocationTemplate() method.

        return ResultUtil::successResult('ok');
    }


}