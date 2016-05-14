<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/losses")
 */
class LossAPIController extends APIController implements LossAPIControllerInterface {

  /**
   * Get a DeclareLoss, found by it's ID.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a DeclareLoss by given ID",
   *   output = "AppBundle\Entity\DeclareLoss"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareLoss to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareLossRepository")
   * @Method("GET")
   */
  public function getLossById(Request $request, $Id)
  {
    $loss = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($loss, 200);
  }


  /**
   * Retrieve either a list of all DeclareLosses or a subset of DeclareLosses with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * }
   *
   * @ApiDoc(
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
   *        "name"="state",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" DeclareLosses to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareLosses",
   *   output = "AppBundle\Entity\DeclareLoss"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getLosses(Request $request)
  {
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $declareLosses = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareLosses = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareLosses), 200);
  }


  /**
   *
   * Create a new DeclareLoss Request.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a DeclareLoss request",
   *   input = "AppBundle\Entity\DeclareLoss",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createLoss(Request $request)
  {
    //Convert front-end message into an array
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject, RequestType::DECLARE_LOSS_ENTITY);

    //Send it to the queue and persist/update any changed state to the database
    $this->sendMessageObjectToQueue($messageObject, RequestType::DECLARE_LOSS_ENTITY, RequestType::DECLARE_LOSS);
    return new JsonResponse($messageObject, 200);
  }


  /**
   *
   * Update existing DeclareLoss Request.
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Update a DeclareLoss request",
   *   input = "AppBundle\Entity\DeclareLoss",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareLossRepository")
   * @Method("PUT")
   */
  public function editLoss(Request $request, $Id)
  {
    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareLossUpdate = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY,
        $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

    $entityManager = $this->getDoctrine()
        ->getManager()
        ->getRepository(Constant::DECLARE_LOSS_REPOSITORY);
    $declareLoss = $entityManager->findOneBy(array (Constant::REQUEST_ID_NAMESPACE => $Id));

    if($declareLoss == null) {
      return new JsonResponse(array("message"=>"No DeclareLoss found with request_id: " . $Id), 204);
    }


    if ($declareLossUpdate->getAnimal() != null) {
      $declareLoss->setAnimal($declareLossUpdate->getAnimal());
    }

    if ($declareLossUpdate->getDateOfDeath() != null) {
      $declareLoss->setDateOfDeath($declareLossUpdate->getDateOfDeath());
    }

    if ($declareLossUpdate->getLocation() != null) {
      $declareLoss->setLocation($declareLossUpdate->getLocation());
    }

    if($declareLossUpdate->getUbnProcessor() != null) {
      $declareLoss->setUbnProcessor($declareLossUpdate->getUbnProcessor());
    }

    if($declareLossUpdate->getReasonOfLoss() != null) {
      $declareLoss->setReasonOfLoss($declareLossUpdate->getReasonOfLoss());
    }

    $declareLoss = $entityManager->update($declareLoss);

    return new JsonResponse($declareLoss, 200);
  }
}