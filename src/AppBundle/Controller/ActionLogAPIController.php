<?php


namespace AppBundle\Controller;


use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse as JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\Serializer\SerializationContext;

/**
 * @Route("/api/v1/log/action")
 */
class ActionLogAPIController extends APIController
{
    /**
     * Retrieve all userActionTypes of current actionLogs.
     *
     * ### Result Body ###
     *
     *  {
     *      "result":
     *          [
     *              "ANIMAL_DETAILS_EDIT",
     *              "CONTACT_EMAIL",
     *              "DECLARE_ARRIVAL",
     *              "DECLARE_DEPART",
     *              "DECLARE_EXPORT",
     *              "DECLARE_LOSS",
     *              "DECLARE_TAG_REPLACE",
     *              "DECLARE_WEIGHT_CREATE",
     *              "GENDER_CHANGE",
     *              "HEALTH_STATUS_UPDATE",
     *              "MATE_CREATE",
     *              "NON_IR_REVOKE",
     *              "REVOKE_DECLARATION",
     *              "TREATMENT_TEMPLATE_CREATE",
     *              "TREATMENT_TEMPLATE_DELETE",
     *              "TREATMENT_TEMPLATE_EDIT",
     *              "USER_LOGIN",
     *              "USER_PASSWORD_CHANGE",
     *              "USER_PASSWORD_RESET"
     *          ]
     *  }
     *
     *
     * @ApiDoc(
     *   section = "Action Log",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="user_account_id",
     *        "dataType"="integer",
     *        "required"=false,
     *        "description"="id of location owner to retrieve userActionTypes for",
     *        "format"="?user_account_id=99999"
     *      },
     *   },
     *   resource = true,
     *   description = "Retrieve all userActionTypes of current actionLogs",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-types")
     * @Method("GET")
     */
    public function getUserActionTypes(Request $request)
    {
        return $this->get('api.action_log')->getUserActionTypes($request);
    }


    /**
     * Retrieve actionLogs filtered by given query parameters
     *
     * ### Result Body ###
     *
     *  {
     *      "result": [
     *          {
     *              "log_date": "2016-09-29T17:45:47+0200",
     *              "user_account": {
     *                  "first_name": "Reinard",
     *                  "last_name": "Everts",
     *                  "relation_number_keeper": "203719934",
     *                  "type": "Client"
     *              },
     *              "action_by": {
     *                  "first_name": "Reinard",
     *                  "last_name": "Everts",
     *                  "type": "Employee"
     *              },
     *              "user_action_type": "HEALTH_STATUS_UPDATE",
     *              "description": "new health statusses for ubn: 1674459. maedi visna: FREE_2_YEAR. scrapie: RESISTANT",
     *              "is_completed": true,
     *              "is_user_environment": false
     *          },
     *          {
     *              "log_date": "2016-10-22T15:21:22+0200",
     *              "user_account": {
     *                  "first_name": "Reinard",
     *                  "last_name": "Everts",
     *                  "relation_number_keeper": "203719934",
     *                  "type": "Client"
     *              },
     *              "action_by": {
     *                  "first_name": "Reinard",
     *                  "last_name": "Everts",
     *                  "type": "Employee"
     *              },
     *              "user_action_type": "DECLARE_EXPORT",
     *              "description": "ubn: 1674459. export. uln: NL100153124415",
     *              "is_completed": true,
     *              "is_user_environment": true
     *          }
     *      ]
     *  }
     *
     * @ApiDoc(
     *   section = "Action Log",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   parameters={
     *      {
     *        "name"="start_date",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="minimum log date of action logs",
     *        "format"="?start_date=YYYY-MM-DD"
     *      },
     *      {
     *        "name"="end_date",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="maximum log date of action logs",
     *        "format"="?end_date=YYYY-MM-DD"
     *      },
     *      {
     *        "name"="user_action_type",
     *        "dataType"="string",
     *        "required"=false,
     *        "description"="user_action_type to retrieve",
     *        "format"="?user_action_type=USER_ACTION_TYPE"
     *      },
     *      {
     *        "name"="user_account_id",
     *        "dataType"="integer",
     *        "required"=false,
     *        "description"="id of location owner to retrieve",
     *        "format"="?user_account_id=99999"
     *      },
     *   },
     *   resource = true,
     *   description = "Retrieve actionLogs filtered by given query parameters. A Client can only see their own logs",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getActionLogs(Request $request)
    {
        return $this->get('api.action_log')->getActionLogs($request);
    }


    /**
     * Retrieve all accountOwnerIds in the actionLogs.
     *
     * ### Result Body ###
     *
     *  {
     *      "result":
     *          [
     *              {
     *                  "id": 2198,
     *                  "first_name": "",
     *                  "last_name": ""
     *              },
     *              {
     *                  "id": 994,
     *                  "first_name": "H.J.",
     *                  "last_name": "Aa"
     *              },
     *              {
     *                  "id": 346,
     *                  "first_name": "A.F.W.M.",
     *                  "last_name": "Aa-Hendriksen"
     *              }
     *          ]
     *  }
     *
     * @ApiDoc(
     *   section = "Action Log",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve all accountOwnerIds in the actionLogs",
     *   statusCodes={200="Returned when successful"},
     *   input="json",
     *   output="json"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-account-owners")
     * @Method("GET")
     */
    public function getAccountOwnerIds(Request $request)
    {
        return $this->get('api.action_log')->getAccountOwnerIds();
    }
}