<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Output\ProcessorOutput;
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
   * Create a RetrieveUbnDetails request
   *
   * @ApiDoc(
   *   section = "UBNs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Post a RetrieveUbnDetails request"
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
      $client = $this->getAccountOwner($request);
      $loggedInUser = $this->getUser();
      $location = $this->getSelectedLocation($request);

      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_UBN_DETAILS_ENTITY, $content, $client, $loggedInUser, $location);

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject);

      //Send it to the queue and persist/update any changed state to the database
      $this->sendMessageObjectToQueue($messageObject);

      return new JsonResponse($messageObject, 200);
    }


  /**
   *
   * Get list of UBN Processors.
   *
   * @ApiDoc(
   *   section = "UBNs",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Get list of UBN Processors"
   * )
   *
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/processors")
   * @Method("GET")
   */
  public function getUbnProcessors(Request $request)
  {
    $processors = $this->getDoctrine()->getRepository(Constant::PROCESSOR_REPOSITORY)->findAll();
    $includeNames = true;
    $output = ProcessorOutput::create($processors, $includeNames);

    return new JsonResponse($output, 200);
  }

}