<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
    $animals = null;
    $animalRepository = $this->getDoctrine()
      ->getRepository(Constant::ANIMAL_REPOSITORY);
    $locationRepository = $this->getDoctrine()
      ->getRepository(Constant::LOCATION_REPOSITORY);

    //Get locations of user
    $locations = $locationRepository->findByUser($this->getAuthenticatedUser($request));

    //Get animals on each location belonging to user
    foreach ($locations as $location) {
      $filterArray = array (Constant::LOCATION_NAMESPACE => $location->getId());

      if (!$request->query->has(Constant::ANIMAL_TYPE_NAMESPACE) && !$request->query->has(Constant::ALIVE_NAMESPACE)) {
        //select all animals, belonging to user with no filters
        $animals = $animalRepository->findByTypeOrState(null, $filterArray);
      }
      else {
        if (!$request->query->has(Constant::ANIMAL_TYPE_NAMESPACE) && $request->query->has(Constant::ALIVE_NAMESPACE)) {
          //filter animals by given isAlive state:{true, false}, belonging to user
          $isAlive = $request->query->get(Constant::ALIVE_NAMESPACE);
          $filterArray = array (
            Constant::LOCATION_NAMESPACE => $location->getId(),
            Constant::IS_ALIVE_NAMESPACE => ($isAlive === Constant::BOOLEAN_TRUE_NAMESPACE)
          );
          $animals = $animalRepository->findByTypeOrState(null, $filterArray);
        }
        else {
          if ($request->query->has(Constant::ANIMAL_TYPE_NAMESPACE) && !$request->query->has(Constant::ALIVE_NAMESPACE)) {
            $animalType = $request->query->get(Constant::ANIMAL_TYPE_NAMESPACE);
            $animals = $animalRepository->findByTypeOrState($animalType, $filterArray);
          }
          else {
            //filter animals by given animal-type: {ram, ewe, neuter} and isAlive state: {true, false}, belonging to user
            $animalType = $request->query->get(Constant::ANIMAL_TYPE_NAMESPACE);
            $isAlive = $request->query->get(Constant::ALIVE_NAMESPACE);
            $filterArray = array (
              Constant::LOCATION_NAMESPACE => $location->getId(),
              Constant::IS_ALIVE_NAMESPACE => ($isAlive === Constant::BOOLEAN_TRUE_NAMESPACE)
            );
            $animals = $animalRepository->findByTypeOrState($animalType, $filterArray);
          }
        }
      }
    }

    return new JsonResponse(array (Constant::RESULT_NAMESPACE => $animals), 200);
  }

  /**
   * Retrieve an animal, found by it's Id.
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
   *   description = "Retrieve an Animal by given ID",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @param $animal
   * @return JsonResponse
   * @Route("/{Id}")
   * @Method("GET")
   */
  public function getAnimalById(Request $request, $Id) {
    $repository = $this->getDoctrine()
      ->getRepository(Constant::ANIMAL_REPOSITORY);
    $animal = $repository->findByUlnOrPedigree($Id);

    return new JsonResponse($animal, 200);
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

      //Convert the array into an object and add the mandatory values retrieved from the database
      $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $this->getAuthenticatedUser($request));

      //First Persist object to Database, before sending it to the queue
      $this->persist($messageObject);

      //Send it to the queue and persist/update any changed state to the database
      $messageArray = $this->sendMessageObjectToQueue($messageObject);

      return new JsonResponse($messageObject, 200);
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
  function getAnimalDetails(Request $request) {
    //Get content to array
    $content = $this->getContentAsArray($request);

    //Convert the array into an object and add the mandatory values retrieved from the database
    $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMAL_DETAILS_ENTITY, $content, $this->getAuthenticatedUser($request));

    //First Persist object to Database, before sending it to the queue
    //$this->persist($messageObject);

    //Send it to the queue and persist/update any changed state to the database
    $messageArray = $this->sendMessageObjectToQueue($messageObject);

    return new JsonResponse($messageObject, 200);

  }
}