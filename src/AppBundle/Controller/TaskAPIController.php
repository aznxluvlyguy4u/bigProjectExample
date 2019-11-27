<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\TaskService;
use AppBundle\Util\ResultUtil;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class TaskAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/tasks")
 */
class TaskAPIController extends APIController {

    /**
     * Get all tasks
     *
     * @ApiDoc(
     *   section = "Tasks",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Validate whether an accesstoken is valid or not."
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("get")
     */
    public function getTasks(Request $request)
    {
        return ResultUtil::successResult($this->get(TaskService::class)->getTasks($request));
    }

    /**
     * Calculate star ewes.
     *
     * @ApiDoc(
     *   section = "Tasks",
     *   headers={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "required"=true,
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Start a worker task to calculate star ewes"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/star-ewes-calculation")
     * @Method("GET")
     */
    public function getStarEwesCalculationTask(Request $request)
    {
        return $this->get(TaskService::class)->createCalculateStarEwesTask($request);
    }
}
