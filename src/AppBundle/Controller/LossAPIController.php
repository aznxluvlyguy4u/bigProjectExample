<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Enumerator\RequestType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1/losses")
 */
class LossAPIController extends APIController {

  const REQUEST_TYPE  = RequestType::DECLARE_LOSS;
  const MESSAGE_CLASS = RequestType::DECLARE_LOSS_ENTITY;

  /**
   * @var Client
   */
  private $user;
  /**
   *
   * Get a list of DeclareLosses with a given state:{OPEN, CLOSED, DECLINED} or all
   * losses when no state is given.
   *
   *
   * @Route("/status")
   * @Method("GET")
   */
  public function getLossByState(Request $request)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Get a DeclareLoss, found by it's ID.
   *
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareLossRepository")
   * @Method("GET")
   */
  public function getLossById(Request $request,$Id)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Create a DeclareLoss Request.
   *
   * @Route("")
   * @Method("POST")
   */
  public function postNewLoss(Request $request)
  {
    return new JsonResponse("ok", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
    return new JsonResponse("ok", 200);
  }

}