<?php

namespace AppBundle\Controller;

use AppBundle\Component\Modifier\AnimalRemover;
use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
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
      $client = $this->getAuthenticatedUser($request);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);

      $export = $repository->getBirthByRequestId($client, $Id);

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
      $client = $this->getAuthenticatedUser($request);
      $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);

      if(!$stateExists) {
          $declareBirths = $repository->getBirths($client);

      } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

          $declareBirths = new ArrayCollection();

          foreach($repository->getBirths($client, RequestStateType::OPEN) as $birth) {
            $declareBirths->add($birth);
          }

          foreach($repository->getBirths($client, RequestStateType::REVOKING) as $birth) {
              $declareBirths->add($birth);
          }
          foreach($repository->getBirths($client, RequestStateType::FINISHED) as $birth) {
              $declareBirths->add($birth);
          }
          
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
      return new JsonResponse(array('code'=>428, "message" => $message), 428);
    }

    //Get content to array
    $content = $this->getContentAsArray($request);

    //Split up the children in the array into separate messages
    $children = $content->get("children");
    $contentWithoutChildren = $content;
    $contentWithoutChildren->remove("children");

    $returnMessages = new ArrayCollection();

    //Validate ALL children's ULN's BEFORE persisting any animal at all
    $ulns = array();
    foreach($children as $child) {
        $ulnCountryCode = $child[Constant::ULN_COUNTRY_CODE_NAMESPACE];
        $ulnNumber = $child[Constant::ULN_NUMBER_NAMESPACE];
        $verification = $this->isTagUnassigned($ulnCountryCode,
                                               $ulnNumber);
        if(!$verification['isValid']) {
            return $verification['jsonResponse'];
        }
        $ulns[] = $ulnCountryCode . $ulnNumber;
    }

    //Validate all ulns are unique
    if(!Utils::arrayValuesAreUnique($ulns)) {
        return new JsonResponse(array('code' => 428,
            'message' => 'The uln values are valid, but each child should have a unique uln'), 428);
    }

    $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    $loggedInUser = $this->getLoggedInUser($request);

    foreach($children as $child) {

        $contentPerChild = $contentWithoutChildren;
        $contentPerChild->set('animal', $child);

        if($child['is_alive'] == true) { //DeclareBirth with sending a request to IenR
            //Convert the array into an object and add the mandatory values retrieved from the database
            $declareBirthObject = $this->buildMessageObject(RequestType::DECLARE_BIRTH_ENTITY, $contentPerChild, $client, $loggedInUser, $location);

            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($declareBirthObject);

            //Set tags of child to ASSIGNING
            $this->persistNewTagsToAssigning($client, $declareBirthObject->getAnimal());

            //Persist message without animal. That is done after a successful response
            $declareBirthObject->setAnimal(null);
            $this->persist($declareBirthObject);

            $returnMessages->add($messageArray); //TODO when stillborn is complete, move out side if() but still inside foreach

        } else { //DeclareStill born, only persist to database and don't send request to IenR
            //TODO
        }
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

      $content = $this->getContentAsArray($request);
      $client = $this->getAuthenticatedUser($request);
      $loggedInUser = $this->getLoggedInUser($request);
      $location = $this->getSelectedLocation($request);

      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);
      $declareBirth = $entityManager->getBirthByRequestId($client, $content->get("request_id"));

      if($declareBirth == null) {
          $message = 'no message found for the given requestId';
          $messageArray = array('code'=>400, "message" => $message);

          return new JsonResponse($messageArray, 400);
      }

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

      //Validate if tag is available
      $verification = $this->isTagUnassigned($content->get('animal')['uln_country_code'],
          $content->get('animal')['uln_number']);
      if(!$verification['isValid']) {
          return $verification['jsonResponse'];
      }

      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareBirthUpdate = $this->buildEditMessageObject(RequestType::DECLARE_BIRTH_ENTITY,
          $content, $client, $loggedInUser, $location);

      //First Persist object to Database, before sending it to the queue
      $this->persist($declareBirthUpdate);

      //Send it to the queue and persist/update any changed state to the database
      $messageArray = $this->sendEditMessageObjectToQueue($declareBirthUpdate);

    return new JsonResponse($messageArray, 200);
  }

    /**
     *
     * Get DeclareBirths & DeclareStillborns which have failed last responses.
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
     *   description = "Get DeclareBirths & DeclareStillborns which have failed last responses",
     *   input = "AppBundle\Entity\DeclareBirth",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getBirthErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);

        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        $birthRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_RESPONSE_REPOSITORY);
        $declareBirths = $birthRepository->getBirthsWithLastErrorResponses($location, $animalRepository);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('births' => $declareBirths)), 200);
    }


    /**
     *
    /**
     *
     * For the history view, get DeclareBirths & DeclareStillborns which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED.
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
     *   description = "Get DeclareBirths & DeclareStillborns which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED",
     *   input = "AppBundle\Entity\DeclareBirth",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-history")
     * @Method("GET")
     */
    public function getBirthHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        $birthRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_RESPONSE_REPOSITORY);
        $declareBirths = $birthRepository->getBirthsWithLastHistoryResponses($location, $animalRepository);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('births' => $declareBirths)), 200);
    }
}