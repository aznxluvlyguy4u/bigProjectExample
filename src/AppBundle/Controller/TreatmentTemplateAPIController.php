<?php


namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/v1/treatments")
 */
class TreatmentTemplateAPIController extends APIController implements TreatmentTemplateAPIControllerInterface
{
    /**
     * Get default individual treatment templates
     *
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": [
     *          {
     *              "dutchType": "Individueel",
     *              "id": 8,
     *              "description": "Hersenverschijnselen",
     *              "medications": [
     *                  {
     *                      "description": "ebolastop 900%",
     *                      "dosage": 559
     *                  },
     *                  {
     *                      "description": "elixer 695%",
     *                      "dosage": 52.755
     *                  }
     *              ],
     *              "is_active": true,
     *          }
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Get default individual treatment templates",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": [
     *          {
     *              "dutchType": "Individueel",
     *              "id": 8,
     *              "location": {
     *                  "ubn": "1674459"
     *              },
     *              "description": "Hersenverschijnselen",
     *              "medications": [
     *                  {
     *                      "description": "ebolastop 900%",
     *                      "dosage": 559
     *                  },
     *                  {
     *                      "description": "elixer 695%",
     *                      "dosage": 52.755
     *                  }
     *              ],
     *              "is_active": true,
     *              "type": "INDIVIDUAL"
     *          }
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Get individual treatment templates for a specific ubn",
     *   statusCodes={200="Returned when successful",401="unauthorized"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": [
     *          {
     *              "dutchType": "UBN",
     *              "id": 8,
     *              "description": "Myiasisbehandeling",
     *              "medications": [
     *                  {
     *                      "description": "Neogenic recombinator 666%",
     *                      "dosage": 10000000
     *                  },
     *                  {
     *                      "description": "sledgehammer",
     *                      "dosage": 0.0001
     *                  }
     *              ],
     *              "is_active": true,
     *              "type": "LOCATION"
     *          }
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Get default location treatment templates",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": [
     *          {
     *              "dutchType": "UBN",
     *              "id": 8,
     *              "location": {
     *                  "ubn": "1674459"
     *              },
     *              "description": "Diarrhea",
     *              "medications": [
     *                  {
     *                      "description": "purple elixer",
     *                      "dosage": 999
     *                  },
     *                  {
     *                      "description": "sledgehammer",
     *                      "dosage": 0.0001
     *                  }
     *              ],
     *              "is_active": true,
     *              "type": "LOCATION"
     *          }
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Get location treatment templates for a specific ubn",
     *   statusCodes={200="Returned when successful",401="unauthorized"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  {
     *      "location": {
     *          "ubn": "1674459"
     *      },
     *    "description" : "Uierontsteking",
     *    "medications":
     *      [
     *          {
     *              "description": "ubuntuproven 100%",
     *              "dosage": 16.4
     *          },
     *          {
     *              "description": "genetic recombinator 666%",
     *              "dosage": 10000000
     *          }
     *      ]
     *  }
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "dutchType": "Individueel",
     *          "id": 8,
     *          "description": "Uierontsteking",
     *          "medications":
     *          [
     *              {
     *                  "description": "ubuntuproven 100%",
     *                  "dosage": 16.4
     *              },
     *              {
     *                  "description": "genetic recombinator 666%",
     *                  "dosage": 10000000
     *              }
     *          ],
     *          "is_active": true,
     *          "type": "INDIVIDUAL"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Create individual treatment templates for a specific ubn",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  {
     *      "location": {
     *          "ubn": "1674459"
     *      },
     *    "description" : "Myiasisbehandeling",
     *    "medications":
     *      [
     *          {
     *              "description": "ubuntuproven 100%",
     *              "dosage": 16.4
     *          },
     *          {
     *              "description": "genetic recombinator 666%",
     *              "dosage": 10000000
     *          }
     *      ]
     *  }
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "dutchType": "UBN",
     *          "id": 8,
     *          "description": "Myiasisbehandeling",
     *          "medications":
     *          [
     *              {
     *                  "description": "ubuntuproven 100%",
     *                  "dosage": 16.4
     *              },
     *              {
     *                  "description": "genetic recombinator 666%",
     *                  "dosage": 10000000
     *              }
     *          ],
     *          "is_active": true,
     *          "type": "LOCATION"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Create location treatment templates for a specific ubn",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  {
     *      "location": {
     *          "ubn": "1674459"
     *      },
     *    "description" : "Uierontsteking",
     *    "medications":
     *      [
     *          {
     *              "description": "ubuntuproven 100%",
     *              "dosage": 16.4
     *          },
     *          {
     *              "description": "genetic recombinator 666%",
     *              "dosage": 10000000
     *          }
     *      ]
     *  }
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "dutchType": "Individueel",
     *          "id": 8,
     *          "description": "Uierontsteking",
     *          "medications":
     *          [
     *              {
     *                  "description": "ubuntuproven 100%",
     *                  "dosage": 16.4
     *              },
     *              {
     *                  "description": "genetic recombinator 666%",
     *                  "dosage": 10000000
     *              }
     *          ],
     *          "is_active": true,
     *          "type": "INDIVIDUAL"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Edit individual treatment templates",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  {
     *      "location": {
     *          "ubn": "1674459"
     *      },
     *    "description" : "Myiasisbehandeling",
     *    "medications":
     *      [
     *          {
     *              "description": "ubuntuproven 100%",
     *              "dosage": 16.4
     *          },
     *          {
     *              "description": "genetic recombinator 666%",
     *              "dosage": 10000000
     *          }
     *      ]
     *  }
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "dutchType": "UBN",
     *          "id": 8,
     *          "description": "Myiasisbehandeling",
     *          "medications":
     *          [
     *              {
     *                  "description": "ubuntuproven 100%",
     *                  "dosage": 16.4
     *              },
     *              {
     *                  "description": "genetic recombinator 666%",
     *                  "dosage": 10000000
     *              }
     *          ],
     *          "is_active": true,
     *          "type": "LOCATION"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Edit location treatment templates",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "dutchType": "Individueel",
     *          "id": 8,
     *          "description": "Uierontsteking",
     *          "medications":
     *          [
     *              {
     *                  "description": "ubuntuproven 100%",
     *                  "dosage": 16.4
     *              },
     *              {
     *                  "description": "genetic recombinator 666%",
     *                  "dosage": 10000000
     *              }
     *          ],
     *          "is_active": false,
     *          "type": "INDIVIDUAL"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Deactivate individual treatment templates",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
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
     * ### Request body ###
     *
     *  none
     *
     * ### Response body ###
     *
     *  {
     *      "result": {
     *          "dutchType": "UBN",
     *          "id": 8,
     *          "description": "Myiasisbehandeling",
     *          "medications":
     *          [
     *              {
     *                  "description": "ubuntuproven 100%",
     *                  "dosage": 16.4
     *              },
     *              {
     *                  "description": "genetic recombinator 666%",
     *                  "dosage": 10000000
     *              }
     *          ],
     *          "is_active": false,
     *          "type": "LOCATION"
     *      }
     *  }
     *
     * @ApiDoc(
     *   section = "Treatment Template",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="true",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="minimal_output",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="set to false to return more data, it is true by default",
     *        "format"="?active_only=false"
     *      }
     *   },
     *   resource = true,
     *   description = "Deactivate location treatment templates",
     *   statusCodes={200="Returned when successful", 428="Returned for invalid input"},
     *   input="json",
     *   output="json"
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