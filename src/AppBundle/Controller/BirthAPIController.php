<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Export\ExportException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/births")
 */
class BirthAPIController extends APIController implements BirthAPIControllerInterface
{

  /**
   * Retrieve a DeclareBirth, found by it's ID.
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
   *   description = "Retrieve a DeclareBirth by given ID",
   *   output = "AppBundle\Entity\DeclareBirth"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareBirth to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareBirthRepository")
   * @Method("GET")
   */
  public function getBirthById(Request $request, $Id)
  {
      //TODO for phase 2: read a location from the $request and find declareBirths for that location
      $client = $this->getAuthenticatedUser($request);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);

      $export = $repository->getBirthsById($client, $Id);

      return new JsonResponse($export, 200);
  }

  /**
   * Retrieve either a list of all DeclareBirths or a subset of DeclareBirths with a given state-type:
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
   *        "description"=" DeclareBirths to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareBirths",
   *   output = "AppBundle\Entity\DeclareBirth"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getBirths(Request $request)
  {
      //TODO for phase 2: read a location from the $request and find declareBirths for that location
      $client = $this->getAuthenticatedUser($request);
      $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);

      if(!$stateExists) {
          $declareBirths = $repository->getBirths($client);

      } else { //A state parameter was given, use custom filter to find subset
          $state = $request->query->get(Constant::STATE_NAMESPACE);
          $declareBirths = $repository->getBirths($client, $state);
      }

      return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareBirths), 200);
  }

  /**
   * Create a new DeclareBirth request
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
   *   description = "Post a DeclareBirth request",
   *   input = "AppBundle\Entity\DeclareBirth",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createBirth(Request $request)
  {
    $validityCheckUlnOrPedigree = $this->isUlnOrPedigreeCodeValid($request);
    $isValid = $validityCheckUlnOrPedigree['isValid'];

    if(!$isValid) {
      $keyType = $validityCheckUlnOrPedigree['keyType']; // uln  of pedigree
      $animalKind = $validityCheckUlnOrPedigree['animalKind'];
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $messageArray = array('code'=>428, "message" => $message);

      return new JsonResponse($messageArray, 428);
    }

    //Get content to array
    $content = $this->getContentAsArray($request);

    //Split up the children in the array into separate messages
    $children = $content->get("children"); 
    $contentWithoutChildren = $content;
    $contentWithoutChildren->remove("children");

    $returnMessages = new ArrayCollection();

    foreach($children as $child) {
        
        $ulnNumber = $child[Constant::ULN_NUMBER_NAMESPACE];
        $ulnCountryCode = $child[Constant::ULN_COUNTRY_CODE_NAMESPACE];

        $tag = $this->getEntityGetter()->retrieveTag($ulnCountryCode, $ulnNumber);

        if($tag->getTagStatus() == Constant::ASSIGNED_NAMESPACE){
            //TODO redirect to error table / save the incorrect input (?)
            return new JsonResponse(array("Tag already in use", 200), 200);
        }

        $contentPerChild = $contentWithoutChildren;
        $contentPerChild->set('animal', $child);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $declareBirthObject = $this->buildMessageObject(RequestType::DECLARE_BIRTH_ENTITY, $contentPerChild, $this->getAuthenticatedUser($request));

        //First Persist object to Database, before sending it to the queue
        $this->persist($declareBirthObject, RequestType::DECLARE_BIRTH_ENTITY);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($declareBirthObject, RequestType::DECLARE_BIRTH_ENTITY, RequestType::DECLARE_BIRTH);

        $returnMessages->add($declareBirthObject);
    }

    return new JsonResponse($returnMessages, 200);
  }

  /**
   * Update existing DeclareBirth request
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
   *   description = "Update a DeclareBirth request",
   *   input = "AppBundle\Entity\DeclareBirth",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareBirthRepository")
   * @Method("PUT")
   */
  public function updateBirth(Request $request, $Id) {

      //TODO Phase 2: Minimize validity check for all controllers
      $validityCheckUlnOrPedigree = $this->isUlnOrPedigreeCodeValid($request);
      $isValid = $validityCheckUlnOrPedigree['isValid'];

      if(!$isValid) {
          $keyType = $validityCheckUlnOrPedigree['keyType']; // uln  of pedigree
          $animalKind = $validityCheckUlnOrPedigree['animalKind'];
          $message = $keyType . ' of ' . $animalKind . ' not found.';
          $messageArray = array('code'=>428, "message" => $message);

          return new JsonResponse($messageArray, 428);
      }

      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareBirthUpdate = $this->buildEditMessageObject(RequestType::DECLARE_BIRTH_ENTITY,
          $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);
      $declareBirth = $entityManager->updateDeclareBirthMessage($declareBirthUpdate, $Id);

        if($declareBirth == null) {
          return new JsonResponse(array("message"=>"No DeclareBirth found with request_id:" . $Id), 204);
        }

    return new JsonResponse($declareBirth, 200);
  }
}