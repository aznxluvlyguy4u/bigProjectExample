<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Enumerator\RequestType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/ubns")
 */
class UBNAPIController extends APIController implements UBNAPIControllerInterface
{

  /**
   * Create a RetrieveUBNDetails request
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
   *   description = "Post a RetrieveUBNDetails request",
   *   input = "AppBundle\Entity\RetrieveUBNDetails",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  function getUBNDetails(Request $request)
    {
      //Get content to array
      $content = $this->getContentAsArray($request);
      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_UBN_DETAILS_ENTITY, $content, $this->getAuthenticatedUser($request));

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject, RequestType::RETRIEVE_UBN_DETAILS_ENTITY);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($messageObject, RequestType::RETRIEVE_UBN_DETAILS_ENTITY, RequestType::RETRIEVE_UBN_DETAILS);

      return new JsonResponse($messageObject, 200);
    }

}