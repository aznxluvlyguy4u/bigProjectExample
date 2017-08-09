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
class ActionLogController extends APIController
{
    /**
     * Retrieve all userActionTypes of current actionLogs.
     *
     * @ApiDoc(
     *   section = "Log",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
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
     *   description = "Retrieve all userActionTypes of current actionLogs"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-types")
     * @Method("GET")
     */
    public function getUserActionTypes(Request $request)
    {
        return $this->getActionLogService()->getUserActionTypes($request);
    }


    /**
     * Retrieve actionLogs filtered by given query parameters
     *
     * @ApiDoc(
     *   section = "Log",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
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
     *   description = "Retrieve actionLogs filtered by given query parameters"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getActionLogs(Request $request)
    {
        return $this->getActionLogService()->getActionLogs($request);
    }


    /**
     * Retrieve all accountOwnerIds in the actionLogs.
     *
     * @ApiDoc(
     *   section = "Log",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve all accountOwnerIds in the actionLogs"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-account-owners")
     * @Method("GET")
     */
    public function getAccountOwnerIds(Request $request)
    {
        return $this->getActionLogService()->getAccountOwnerIds();
    }
}