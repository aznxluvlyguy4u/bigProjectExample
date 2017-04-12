<?php

namespace AppBundle\Controller;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\AnimalDetailsUpdater;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Message;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\BreedType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\MessageType;
use AppBundle\Enumerator\WorkerTaskType;
use AppBundle\FormInput\AnimalDetails;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Output\AnimalOutput;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\Validator;
use AppBundle\Util\RequestUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
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
    $minimizedOutput = AnimalOutput::createAnimalsArray($animals, $this->getDoctrine()->getManager());

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

    $minimizedOutput = AnimalOutput::createAnimalArray($animal, $this->getDoctrine()->getManager());

    return new JsonResponse($minimizedOutput, 200);
  }

  /**
   * Retrieve all alive, on-location, animals belonging to the given UBN.
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
   *   description = " Retrieve all alive, on-location, animals belonging to the given UBN",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-livestock")
   * @Method("GET")
   */
  public function getLiveStock(Request $request) {
    /** @var Location $location */
    $location = $this->getSelectedLocation($request);
    if($location == null) { return Validator::createJsonResponse('Location cannot be null', 428); }

    /** @var  $animalRepository */
    $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
    $livestock = $animalRepository->getLiveStock($location);
    $livestockAnimals = [];

    /** @var Animal $animal */
    foreach ($livestock as $animal) {
      $livestockAnimals[] = [
        JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
        JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
        JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animal->getPedigreeCountryCode(),
        JsonInputConstant::PEDIGREE_NUMBER =>  $animal->getPedigreeNumber(),
        JsonInputConstant::WORK_NUMBER =>  $animal->getAnimalOrderNumber(),
        JsonInputConstant::GENDER =>  $animal->getGender(),
        JsonInputConstant::DATE_OF_BIRTH =>  $animal->getDateOfBirth(),
        JsonInputConstant::DATE_OF_DEATH =>  $animal->getDateOfDeath(),
        JsonInputConstant::IS_ALIVE =>  $animal->getIsAlive(),
        JsonInputConstant::UBN => $location->getUbn(),
        JsonInputConstant::IS_HISTORIC_ANIMAL => false,
        JsonInputConstant::IS_PUBLIC =>  $animal->isAnimalPublic(),
      ];
    }

    return new JsonResponse(array (Constant::RESULT_NAMESPACE => $livestockAnimals), 200);
  }

  /**
   * Retrieve all historic animals,dead or alive, that ever resided on the given UBN.
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
   *   description = "Retrieve all historic animals,dead or alive, that ever resided on the given UBN",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-historic-livestock")
   * @Method("GET")
   */
  public function getHistoricLiveStock(Request $request) {
    $location = $this->getSelectedLocation($request);
    if($location == null) { return Validator::createJsonResponse('Location cannot be null', 428); }

    /** @var AnimalRepository $repository */
    $repository = $this->getDoctrine()->getRepository(Animal::class);
    $historicLivestock = $repository->getHistoricLiveStock($location);
    $historicLivestockAnimals = [];

    /** @var Animal $animal */
    foreach ($historicLivestock as $animal) {
      $historicLivestockAnimals[] = [
        JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
        JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
        JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animal->getPedigreeCountryCode(),
        JsonInputConstant::PEDIGREE_NUMBER =>  $animal->getPedigreeNumber(),
        JsonInputConstant::WORK_NUMBER =>  $animal->getAnimalOrderNumber(),
        JsonInputConstant::GENDER =>  $animal->getGender(),
        JsonInputConstant::DATE_OF_BIRTH =>  $animal->getDateOfBirth(),
        JsonInputConstant::DATE_OF_DEATH =>  $animal->getDateOfDeath(),
        JsonInputConstant::IS_ALIVE =>  $animal->getIsAlive(),
        JsonInputConstant::UBN => $location->getUbn(),
        JsonInputConstant::IS_HISTORIC_ANIMAL => true,
        JsonInputConstant::IS_PUBLIC =>  $animal->isAnimalPublic(),
      ];
    }

    return new JsonResponse([Constant::RESULT_NAMESPACE => $historicLivestockAnimals], 200);
  }


  /**
   * Retrieve all alive rams in the NSFO database
   *
   * @ApiDoc(
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="Retrieve all alive rams in the NSFO database"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve all alive rams in the NSFO database",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-rams")
   * @Method("GET")
   */
  public function getAllRams(Request $request) {
    /** @var AnimalRepository $animalRepository */
    $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
    $ramsArray = $animalRepository->getAllRams();

    return new JsonResponse(array (Constant::RESULT_NAMESPACE => $ramsArray), 200);
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

      if($client == null) { return Validator::createJsonResponse('Client cannot be null', 428); }
      if($location == null) { return Validator::createJsonResponse('Location cannot be null', 428); }

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
      $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::SUPER_ADMIN);
      if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}

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
   * Update animal details
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
   *   description = "Update animal details",
   *   input = "AppBundle\Entity\RetrieveAnimals",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @param string $ulnString
   * @return JsonResponse
   * @Route("-details/{ulnString}")
   * @Method("PUT")
   */
  function updateAnimalDetails(Request $request, $ulnString) {
    //Get content to array
    $content = $this->getContentAsArray($request);
    /** @var AnimalRepository $repository */
    $repository = $this->getDoctrine()->getRepository(Animal::class);
    $animal = $repository->findAnimalByUlnString($ulnString);

    $isAdminEnv = $content->get(JsonInputConstant::IS_ADMIN_ENV);

    if($animal == null) {
      if($this->getUser() instanceof Employee) {
        $errorMessage = "No animal was found with uln: ".$ulnString;
      } else {
        //For regular users, hide the fact that the animal does not exist in the database at all.
        $errorMessage = "For this account, no animal was found with uln: ".$ulnString;
      }
      return Validator::createJsonResponse($errorMessage, 404);
    }

    if($isAdminEnv) {
      $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::SUPER_ADMIN);
      if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
      
      //Animal Edit from ADMIN environment
      AnimalDetailsUpdater::updateAsAdmin($this->getSerializer(), $animal, $content, $this->getUser());

      if($animal->getLocation()) {
        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($animal->getLocation());
      }

      $output = $this->getDecodedJsonForAnimalDetailsOutputFromAdminEnvironment($animal);

    } else {
      //Animal Edit from USER environment
      AnimalDetailsUpdater::update($this->getManager(), $animal, $content);

      if($animal->getLocation()) {
        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($animal->getLocation());
      }

      $output = AnimalDetailsOutput::create($this->getManager(), $animal, $animal->getLocation());

    }

    return new JsonResponse($output, 200);
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
    $isAdminEnvironment = RequestUtil::getBooleanQuery($request, JsonInputConstant::IS_ADMIN_ENV);

    if($isAdminEnvironment) {
      /** @var AnimalRepository $repository */
      $repository = $this->getDoctrine()->getRepository(Animal::class);
      $animal = $repository->findAnimalByUlnString($ulnString);

      if($animal == null) {
        return Validator::createJsonResponse("No animal was found with uln: ".$ulnString, 404);
      }

      $output = $this->getDecodedJsonForAnimalDetailsOutputFromAdminEnvironment($animal);

    } else {

      $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
      $isAdmin = $validationResult->isValid();

      $location = null;
      if(!$isAdmin) { $location = $this->getSelectedLocation($request); }

      $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
      if(!$animalDetailsValidator->getIsInputValid()) {
        return $animalDetailsValidator->createJsonResponse();
      }

      $animal = $animalDetailsValidator->getAnimal();
      if($location == null) { $location = $animal->getLocation(); }

      $output = AnimalDetailsOutput::create($this->getManager(), $animal, $location);
    }

    return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
  }


  /**
   * Get BreedTypes
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
   *   description = "Get BreedTypes",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("-breed-types")
   * @Method("GET")
   */
  public function getBreedTypes(Request $request) {
    if(RequestUtil::getBooleanQuery($request, JsonInputConstant::IS_DUTCH)) {
      $output = BreedType::getAllInDutch();
    } else {
      $output = BreedType::getAll();
    }
    return new JsonResponse([Constant::RESULT_NAMESPACE => $output], 200);
  }


  /**
   *
   * Change the gender of an Animal for a given ULN. For example NL100029511721
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
   *   description = "Change the gender of an Animal for a given ULN",
   *   input = "AppBundle\Entity\Animals",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   *
   * @param Request $request the request object
   * @return jsonResponse
   * @Route("-gender")
   * @Method("POST")
   */
  public function changeGenderOfUln(Request $request) {
    $em = $this->getDoctrine()->getManager();
    $content = $this->getContentAsArray($request);
    $animal = null;
    
    //Check if mandatory field values are given
    if(!$content['uln_number'] || !$content['uln_country_code'] || !$content['gender']) {
      $statusCode = 400;
      return new JsonResponse(
        array(
          Constant::RESULT_NAMESPACE => array(
              'code'=> $statusCode,
              'message'=> "ULN number, country code is missing or gender is not specified."
          )
        ), $statusCode
      );
    }

    //Try retrieving animal
    $animal = $this->getDoctrine()
      ->getRepository(Constant::ANIMAL_REPOSITORY)
      ->findByUlnCountryCodeAndNumber($content['uln_country_code'] , $content['uln_number']);
   
    if ($animal == null) {
      $statusCode = 204;
      return new JsonResponse(
        array(
          Constant::RESULT_NAMESPACE => array (
            'code' => $statusCode,
            "message" => "No animal found with ULN: " . $content['uln_country_code'] . $content['uln_number']
          )
      ), $statusCode);
    }
    
    //Try to change animal gender
    $gender = $content->get('gender');
    $genderChanger = new GenderChanger($em);
    $targetGender = null;
    $result = null;

    switch ($gender) {
      case AnimalObjectType::EWE:
        $targetGender = "FEMALE";
        $result = $genderChanger->changeToGender($animal, Ewe::class, $this->getUser());
        break;
      case AnimalObjectType::RAM:
        $targetGender = "MALE";
        $result = $genderChanger->changeToGender($animal, Ram::class, $this->getUser());
        break;
      case AnimalObjectType::NEUTER:
        $targetGender = "NEUTER";
        $result = $genderChanger->changeToGender($animal, Neuter::class, $this->getUser());
        break;
    }

    //An exception on the request has occured, return json response error message
    if($result instanceof JsonResponse) {
      return $result;
    }

    //FIXME Temporarily workaround, for returning the reflected gender change, it is persisted, though the updated fields is not returned.
    $result->setGender($targetGender);

    //Clear cache for this location, to reflect changes on the livestock
    $this->clearLivestockCacheForLocation($this->getSelectedLocation($request), $animal);
    
    $minimizedOutput = AnimalOutput::createAnimalArray($animal, $this->getDoctrine()->getManager());

    return new JsonResponse($minimizedOutput, 200);
  }

}