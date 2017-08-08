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
        return $this->getActionLogService()->getUserActionTypes();
    }
}