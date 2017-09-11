<?php


namespace AppBundle\Service;


use AppBundle\Component\AnimalDetailsUpdater;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\AnimalAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Output\AnimalOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;

class AnimalService extends DeclareControllerServiceBase implements AnimalAPIControllerInterface
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllAnimalsByTypeOrState(Request $request)
    {
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

        $client = $this->getAccountOwner($request);

        $animals = $this->getManager()->getRepository(Animal::class)->findOfClientByAnimalTypeAndIsAlive($client, $animalType, $isAlive);
        $minimizedOutput = AnimalOutput::createAnimalsArray($animals, $this->getManager());
        return ResultUtil::successResult($minimizedOutput);
    }


    /**
     * @param Request $request
     * @param $uln
     * @return JsonResponse
     */
    public function getAnimalById(Request $request, $uln)
    {
        $animal = $this->getManager()->getRepository(Animal::class)->findByUlnOrPedigree($uln, true);
        $minimizedOutput = AnimalOutput::createAnimalArray($animal, $this->getManager());
        return new JsonResponse($minimizedOutput, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLiveStock(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        $livestock = $this->getManager()->getRepository(Animal::class)->getLiveStock($location, $this->getCacheService());
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

        return ResultUtil::successResult($livestockAnimals);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistoricLiveStock(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        $historicLivestock = $this->getManager()->getRepository(Animal::class)
            ->getHistoricLiveStock($location, $this->getCacheService());
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

        return ResultUtil::successResult($historicLivestockAnimals);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllRams(Request $request)
    {
        return ResultUtil::successResult($this->getManager()->getRepository(Animal::class)->getAllRams());
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createRetrieveAnimals(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        if($client == null) { return ResultUtil::errorResult('Client cannot be null', 428); }
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($messageObject);

        return ResultUtil::successResult($messageArray);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createRetrieveAnimalsForAllLocations(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $message = $this->syncAnimalsForAllLocations($admin)[Constant::MESSAGE_NAMESPACE];
        return ResultUtil::successResult($message);
    }


    /**
     * @param $loggedInUser
     * @return array
     */
    public function syncAnimalsForAllLocations($loggedInUser)
    {
        $allLocations = $this->getManager()->getRepository(Location::class)->findAll();
        $content = new ArrayCollection();
        $count = 0;

        /** @var Location $location */
        foreach($allLocations as $location) {
            $client = $location->getCompany()->getOwner();

            //Convert the array into an object and add the mandatory values retrieved from the database
            $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $client, $loggedInUser, $location);

            //First Persist object to Database, before sending it to the queue
            $this->persist($messageObject);

            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($messageObject);

            $count++;
        }

        $total = sizeof($allLocations);
        $message = "THE ANIMALS HAVE BEEN SYNCED FOR " . $count . " OUT OF " . $total . " TOTAL LOCATIONS (UBNS)";

        return array('message' => $message,
            'count' => $count);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    function createAnimalDetails(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
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
     * @param Request $request
     * @param string $ulnString
     * @return JsonResponse
     */
    function updateAnimalDetails(Request $request, $ulnString)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArray($request);

        /** @var Animal $animal */
        $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnString);

        if($animal == null) {
            return ResultUtil::errorResult("For this account, no animal was found with uln: " . $ulnString, 204);
        }

        AnimalDetailsUpdater::update($this->getManager(), $animal, $content);

        $location = $this->getSelectedLocation($request);

        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($location);

        $output = AnimalDetailsOutput::create($this->getManager(), $animal, $animal->getLocation());
        return new JsonResponse($output, 200);
    }


    /**
     * @param Request $request
     * @param string $ulnString
     * @return JsonResponse
     */
    public function getAnimalDetailsByUln(Request $request, $ulnString)
    {
        $admin = $this->getEmployee();
        $isAdmin = AdminValidator::isAdmin($admin, AccessLevelType::ADMIN);

        $location = null;
        if(!$isAdmin) { $location = $this->getSelectedLocation($request); }

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
        if(!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }

        $animal = $animalDetailsValidator->getAnimal();
        if($location == null) { $location = $animal->getLocation(); }

        $output = AnimalDetailsOutput::create($this->getManager(), $animal, $location);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse|Animal|null
     */
    public function changeGenderOfUln(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
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
        $animal = $this->getManager()->getRepository(Animal::class)
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
        $genderChanger = new GenderChanger($this->getManager());
        $oldGender = $animal->getGender();
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

        ActionLogWriter::editGender($this->getManager(), $this->getAccountOwner($request),
            $this->getEmployee(), $oldGender, $targetGender);

        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($this->getSelectedLocation($request), $animal);

        $minimizedOutput = AnimalOutput::createAnimalArray($animal, $this->getManager());
        return new JsonResponse($minimizedOutput, 200);
    }
}