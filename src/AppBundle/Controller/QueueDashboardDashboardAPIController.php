<?php


namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\QueueDashboardService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


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
        return $this->get(QueueDashboardService::class)->getQueueSizes($request);
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
        return $this->get(QueueDashboardService::class)->moveErrorQueueMessages($request);
    }

}