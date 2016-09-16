<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Employee;
use AppBundle\FormInput\AnimalDetails;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Output\AnimalOutput;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\DataMapper\RadioListMapper;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Enumerator\RequestType;

/**
 * @Route("/api/v1/animals")
 */
class AnimalAPIController extends APIController implements AnimalAPIControllerInterface {

  /**
   * Retrieve a list of animals. Animal-types are: {ram, ewe, neuter}
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
   *        "name"="type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" animal-type to retrieve: ram, ewe, neuter",
   *        "format"="?type=animal-type"
   *      },
   *      {
   *        "name"="alive",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"="animal life-state to retrieve: true, false",
   *        "format"="?alive=live-state"
   *      },
   *   },
   *   resource = true,
   *   description = "Retrieve a list of animals",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getAllAnimalsByTypeOrState(Request $request) {

    if($request->query->has(Constant::ANIMAL_TYPE_NAMESPACE)) {
      $animalTypeMaybeNotAllCaps = $request->query->get(Constant::ANIMAL_TYPE_NAMESPACE);
      $animalType = strtoupper($animalTypeMaybeNotAllCaps);
    } else {
      $animalType = null;
    }

    if($request->query->has(Constant::ALIVE_NAMESPACE)) {
      $isAlive = $request->query->get(Constant::ALIVE_NAMESPACE);
    } else {
      $isAlive = null;
    }

    //TODO Phase 2 Admin must be able to search all animals for which he is authorized.

    $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
    $client = $this->getAuthenticatedUser($request);

    $animals = $animalRepository->findOfClientByAnimalTypeAndIsAlive($client, $animalType, $isAlive);
    $minimizedOutput = AnimalOutput::createAnimalsArray($animals);

    return new JsonResponse(array (Constant::RESULT_NAMESPACE => $minimizedOutput), 200);
  }

  /**
   * Retrieve an animal, found by it's ULN. For example NL100029511721
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
   *   description = "Retrieve an Animal by given ULN",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @param $uln
   * @return JsonResponse
   * @Route("/{uln}")
   * @Method("GET")
   */
  public function getAnimalById(Request $request, $uln) {
    $repository = $this->getDoctrine()
      ->getRepository(Constant::ANIMAL_REPOSITORY);
    $animal = $repository->findByUlnOrPedigree($uln, true);

    $minimizedOutput = AnimalOutput::createAnimalArray($animal);

    return new JsonResponse($minimizedOutput, 200);
  }

  /**
   * Retrieve all alive on-location animals belonging to this Client: De Stallijst
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
   *   description = " Retrieve all alive on-location animals belonging to this Client",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-livestock")
   * @Method("GET")
   */
  public function getLiveStock(Request $request) {
    $client = $client = $this->getAuthenticatedUser($request);
    $location = $this->getSelectedLocation($request);
    $animals = $this->getDoctrine()
        ->getRepository(Constant::ANIMAL_REPOSITORY)->getLiveStock($location);

    $minimizedOutput = AnimalOutput::createAnimalsArray($animals, $this->getDoctrine()->getEntityManager());

    return new JsonResponse(array (Constant::RESULT_NAMESPACE => $minimizedOutput), 200);
  }

  /**
   * Create a RetrieveAnimal request
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
   *   description = "Post a RetrieveAnimals request",
   *   input = "AppBundle\Entity\RetrieveAnimals",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-sync")
   * @Method("POST")
   */
  public function createRetrieveAnimals(Request $request) {
    {
      //Get content to array
      $content = $this->getContentAsArray($request);
      $client = $this->getAuthenticatedUser($request);
      $loggedInUser = $this->getLoggedInUser($request);
      $location = $this->getSelectedLocation($request);

      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $client, $loggedInUser, $location);

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject);

      //Send it to the queue and persist/update any changed state to the database
      $messageArray = $this->sendMessageObjectToQueue($messageObject);

      return new JsonResponse($messageArray, 200);
    }
  }

  /**
   * Create RetrieveAnimal requests for all clients.
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
   *   description = "Create RetrieveAnimal requests for all clients",
   *   input = "AppBundle\Entity\RetrieveAnimals",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-sync-all")
   * @Method("POST")
   */
  public function createRetrieveAnimalsForAllLocations(Request $request) {
    {
      $loggedInUser = $this->getLoggedInUser($request);
      
      //Any logged in user can sync all animals
      $message = $this->syncAnimalsForAllLocations($loggedInUser)[Constant::MESSAGE_NAMESPACE];

      return new JsonResponse(array(Constant::RESULT_NAMESPACE => $message), 200);
    }
  }

  /**
   * Create a RetrieveAnimalDetails request
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
   *   description = "Post a RetrieveAnimals request",
   *   input = "AppBundle\Entity\RetrieveAnimals",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-details")
   * @Method("POST")
   */
  function createAnimalDetails(Request $request) {
    //Get content to array
    $content = $this->getContentAsArray($request);
    $client = $this->getAuthenticatedUser($request);
    $loggedInUser = $this->getLoggedInUser($request);
    $location = $this->getSelectedLocation($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY, $content, $client, $loggedInUser, $location);

    //First Persist object to Database, before sending it to the queue
    $this->persist($messageObject);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);

    return new JsonResponse($messageArray, 200);
  }

  /**
   * Get Animal Details by ULN. For example NL100029511721
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
   *   description = "Retrieve an Animal by ULN",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @param String $ulnString
   * @return JsonResponse
   * @Route("-details/{ulnString}")
   * @Method("GET")
   */
  public function getAnimalDetailsByUln(Request $request, $ulnString) {

    $client = $this->getAuthenticatedUser($request);
    $animal = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->getAnimalByUlnString($client, $ulnString);

    if($animal == null) {
      return new JsonResponse(array('code'=>404, "message" => "For this account, no animal was found with uln: " . $ulnString), 404);
    }

    $output = AnimalDetailsOutput::create($this->getDoctrine()->getEntityManager(), $animal);
    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $output), 200);
  }

  /**
   *
   * Update Animal Details for the given ULN. For example NL100029511721
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
   *   description = "Update Animal Details for the given ULN",
   *   input = "AppBundle\Entity\Animals",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @param String $ulnString
   * @return jsonResponse
   * @Route("-details/{ulnString}")
   * @Method("PUT")
   */
  public function editAnimalDetailsByUln(Request $request, $ulnString) {

    $client = $this->getAuthenticatedUser($request);
    $animal = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY)->getAnimalByUlnString($client, $ulnString);

    if($animal == null) {
      return new JsonResponse(array('code'=>404, "message" => "For this account, no animal was found with uln: " . $ulnString), 404);
    }

    $content = $this->getContentAsArray($request);

    //TODO for this phase editing AnimalDetails is deactivated
    //TODO keep history of changes

    //Persist updated changes and return the updated values
    $animal = AnimalDetails::update($animal, $content);
    $this->getDoctrine()->getManager()->persist($animal);
    $this->getDoctrine()->getManager()->flush();

    $outputArray = AnimalDetailsOutput::create($this->getDoctrine()->getEntityManager(), $animal);

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
  }
  
}