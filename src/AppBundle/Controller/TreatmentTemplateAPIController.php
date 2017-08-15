<?php


namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/treatments")
 */
class TreatmentTemplateAPIController extends APIController implements TreatmentTemplateAPIControllerInterface
{
    /**
     * Get default individual treatment templates
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get default individual treatment templates"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/template/individual")
     * @Method("GET")
     */
    function getIndividualDefaultTemplates(Request $request)
    {
        return $this->getTreatmentTemplateService()->getIndividualDefaultTemplates($request);
    }

    /**
     * Get individual treatment templates for a specific ubn
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get individual treatment templates for a specific ubn"
     * )
     * @param Request $request the request object
     * @param $ubn
     * @return JsonResponse
     * @Route("/template/individual/{ubn}")
     * @Method("GET")
     */
    function getIndividualSpecificTemplates(Request $request, $ubn)
    {
        return $this->getTreatmentTemplateService()->getIndividualSpecificTemplates($request, $ubn);
    }

    /**
     * Get default location treatment templates
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get default location treatment templates"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/template/location")
     * @Method("GET")
     */
    function getLocationDefaultTemplates(Request $request)
    {
        return $this->getTreatmentTemplateService()->getLocationDefaultTemplates($request);
    }

    /**
     * Get location treatment templates for a specific ubn
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get location treatment templates for a specific ubn"
     * )
     * @param Request $request the request object
     * @param $ubn
     * @return JsonResponse
     * @Route("/template/location/{ubn}")
     * @Method("GET")
     */
    function getLocationSpecificTemplates(Request $request, $ubn)
    {
        return $this->getTreatmentTemplateService()->getLocationSpecificTemplates($request, $ubn);
    }

    /**
     * Create individual treatment templates for a specific ubn
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create individual treatment templates for a specific ubn"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/individual/template")
     * @Method("POST")
     */
    function createIndividualTemplate(Request $request)
    {
        return $this->getTreatmentTemplateService()->createIndividualTemplate($request);
    }

    /**
     * Create location treatment templates for a specific ubn
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Create location treatment templates for a specific ubn"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/location/template")
     * @Method("POST")
     */
    function createLocationTemplate(Request $request)
    {
        return $this->getTreatmentTemplateService()->createLocationTemplate($request);
    }

    /**
     * Edit individual treatment templates
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Edit individual treatment templates"
     * )
     * @param Request $request the request object
     * @param int $templateId
     * @return JsonResponse
     * @Route("/individual/template/{templateId}")
     * @Method("PUT")
     */
    function editIndividualTemplate(Request $request, $templateId)
    {
        return $this->getTreatmentTemplateService()->editIndividualTemplate($request, $templateId);
    }

    /**
     * Edit location treatment templates
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Edit location treatment templates"
     * )
     * @param Request $request the request object
     * @param int $templateId
     * @return JsonResponse
     * @Route("/location/template/{templateId}")
     * @Method("PUT")
     */
    function editLocationTemplate(Request $request, $templateId)
    {
        return $this->getTreatmentTemplateService()->editLocationTemplate($request, $templateId);
    }

    /**
     * Deactivate individual treatment templates
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Deactivate individual treatment templates"
     * )
     * @param Request $request the request object
     * @param int $templateId
     * @return JsonResponse
     * @Route("/individual/template/{templateId}")
     * @Method("DELETE")
     */
    function deleteIndividualTemplate(Request $request, $templateId)
    {
        return $this->getTreatmentTemplateService()->deleteIndividualTemplate($request, $templateId);
    }

    /**
     * Deactivate location treatment templates
     *
     * @ApiDoc(
     *   section = "Treatment",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Deactivate location treatment templates"
     * )
     * @param Request $request the request object
     * @param int $templateId
     * @return JsonResponse
     * @Route("/location/template/{templateId}")
     * @Method("DELETE")
     */
    function deleteLocationTemplate(Request $request, $templateId)
    {
        return $this->getTreatmentTemplateService()->deleteLocationTemplate($request, $templateId);
    }


}