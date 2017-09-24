<?php


namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


/**
 * @Route("/api/v1/queues")
 */
class QueueDashboardDashboardAPIController extends APIController implements QueueDashboardAPIControllerInterface
{

    /**
     * Get overview of all queue sizes.
     *
     * @ApiDoc(
     *   section = "Queues",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve a DeclareArrival by given ID"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-sizes")
     * @Method("GET")
     */
    function getQueueSizes(Request $request)
    {
        return $this->get('app.queue_dashboard')->getQueueSizes($request);
    }


    /**
     * Move all messages in error queues to their primary queue.
     *
     * @ApiDoc(
     *   section = "Queues",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Move all messages in error queues to their primary queue"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-errors-reset")
     * @Method("POST")
     */
    function moveErrorQueueMessages(Request $request)
    {
        return $this->get('app.queue_dashboard')->moveErrorQueueMessages($request);
    }

}