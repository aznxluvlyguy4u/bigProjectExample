<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
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
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_REPOSITORY);

    $loss = $repository->getLossByRequestId($client, $Id);

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
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_REPOSITORY);

    if(!$stateExists) {
      $declareLosses = $repository->getLosses($client);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareLosses = new ArrayCollection();
      foreach($repository->getLosses($client, RequestStateType::OPEN) as $loss) {
        $declareLosses->add($loss);
      }
      foreach($repository->getLosses($client, RequestStateType::REVOKING) as $loss) {
        $declareLosses->add($loss);
      }
      foreach($repository->getLosses($client, RequestStateType::FINISHED) as $loss) {
        $declareLosses->add($loss);
      }
      
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareLosses = $repository->getLosses($client, $state);
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
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    //TODO Get location from header
    $location = $client->getCompanies()[0]->getLocations()[0];

    //Client can only report a loss of own animals
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    if(!$isAnimalOfClient) {
      return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
    }
    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $client, $location);

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject);
    $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);
    return new JsonResponse($messageArray, 200);
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
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    //TODO Get location from header
    $location = $client->getCompanies()[0]->getLocations()[0];

    //Client can only report a loss of own animals
    $animal = $content->get(Constant::ANIMAL_NAMESPACE);
    $isAnimalOfClient = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->verifyIfClientOwnsAnimal($client, $animal);

    if(!$isAnimalOfClient) {
      return new JsonResponse(array('code'=>428, "message" => "Animal doesn't belong to this account."), 428);
    }

    //Convert the array into an object and add the mandatory values retrieved from the database
    $declareLossUpdate = $this->buildMessageObject(RequestType::DECLARE_LOSS_ENTITY, $content, $client, $location);

    $entityManager = $this->getDoctrine()->getManager()->getRepository(Constant::DECLARE_LOSS_REPOSITORY);
    $messageObject = $entityManager->updateDeclareLossMessage($declareLossUpdate, $client, $Id);

    if($messageObject == null) {
      return new JsonResponse(array("message"=>"No DeclareLoss found with request_id: " . $Id), 204);
    }

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendEditMessageObjectToQueue($messageObject);
    $this->persistAnimalTransferringStateAndFlush($messageObject->getAnimal());

    //Persist object to Database
    $this->persist($messageObject);

    return new JsonResponse($messageArray, 200);
  }


  /**
   *
   * Get DeclareLosses which have failed last responses.
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
   *   description = "Get DeclareLosses which have failed last responses",
   *   input = "AppBundle\Entity\DeclareLoss",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-errors")
   * @Method("GET")
   */
  public function getLossErrors(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_RESPONSE_REPOSITORY);
    $declareLosses = $repository->getLossesWithLastErrorResponses($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareLosses), 200);
  }


  /**
   *
   * For the history view, get DeclareLosses which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED
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
   *   description = "Get DeclareLosses which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED",
   *   input = "AppBundle\Entity\DeclareLosses",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-history")
   * @Method("GET")
   */
  public function getLossHistory(Request $request)
  {
    $client = $this->getAuthenticatedUser($request);

    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_LOSS_RESPONSE_REPOSITORY);
    $declareLosses = $repository->getLossesWithLastHistoryResponses($client);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareLosses),200);
  }
}