<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\AnimalAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Output\AnimalOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\GenderChanger;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use AppBundle\Validation\AnimalDetailsValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnimalService extends DeclareControllerServiceBase implements AnimalAPIControllerInterface
{

    /**
     * @param Request $request
     * @return JsonResponse|bool
     */
    public function getAnimals(Request $request)
    {
        if (RequestUtil::getBooleanQuery($request, QueryParameter::PLAIN_TEXT_INPUT, true)) {
            return $this->getAnimalsByPlainTextInput($request);
        }

        return $this->getAllAnimalsByTypeOrState($request);
    }


    /**
     * @param Request $request
     * @return JsonResponse|bool
     */
    private function getAnimalsByPlainTextInput(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $validationResult = $this->validateAnimalsByPlainTextInputRequest($request);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        $content = RequestUtil::getContentAsArray($request);
        $plainTextInput = $content->get(JsonInputConstant::PLAIN_TEXT_INPUT);
        $separator = $content->get(JsonInputConstant::SEPARATOR);

        $ubns = [];
        if ($content->containsKey(JsonInputConstant::UBNS)) {
            $ubns = $content->get(JsonInputConstant::UBNS);
        }

        $incorrectInputs = [];

        $ulnPartsArray = [];
        $stnPartsArray = [];

        $validUlns = [];
        $validStns = [];

        $parts = explode($separator, $plainTextInput);
        foreach ($parts as $part) {
            $ulnOrStnString = StringUtil::removeSpaces($part);

            if ($ulnOrStnString === '') {
                continue;
            }

            if (Validator::verifyUlnFormat($ulnOrStnString, false)) {
                $ulnParts = Utils::getUlnFromString($ulnOrStnString);
                $ulnPartsArray[] = $ulnParts;
                $validUlns[$ulnOrStnString] = $ulnOrStnString;

            } elseif (Validator::verifyPedigreeCountryCodeAndNumberFormat($ulnOrStnString, false)) {
                $stnParts = Utils::getStnFromString($ulnOrStnString);
                $stnPartsArray[] = $stnParts;
                $validStns[$ulnOrStnString] = $ulnOrStnString;

            } else {
                $incorrectInputs[] = trim($part);
            }
        }

        try {
            $animals = $this->getManager()->getRepository(Animal::class)
                ->findAnimalsByUlnPartsOrStnPartsOrUbns($ulnPartsArray, $stnPartsArray, $ubns);
        } catch (\Exception $exception) {
            return ResultUtil::errorResult($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $includeLitter = RequestUtil::getBooleanQuery($request, QueryParameter::INCLUDE_LITTER, false);
        $serializedAnimalsOutput = self::getSerializedAnimalsInBatchEditFormat($this, $animals, $includeLitter);

        $ulnsWithMissingAnimals = [];
        $stnsWithMissingAnimals = [];

        foreach ($validStns as $stn) {
            if (!key_exists($stn, $serializedAnimalsOutput[JsonInputConstant::FOUND_STNS])) {
                $stnsWithMissingAnimals[] = $stn;
            }
        }

        foreach ($validUlns as $uln) {
            if (!key_exists($uln, $serializedAnimalsOutput[JsonInputConstant::FOUND_ULNS])) {
                $ulnsWithMissingAnimals[] = $uln;
            }
        }


        return ResultUtil::successResult([
            JsonInputConstant::ANIMALS => $serializedAnimalsOutput[JsonInputConstant::ANIMALS],
            JsonInputConstant::ULNS_WITHOUT_FOUND_ANIMALS => $ulnsWithMissingAnimals,
            JsonInputConstant::STNS_WITHOUT_FOUND_ANIMALS => $stnsWithMissingAnimals,
            ReportLabel::INVALID => $incorrectInputs,
        ]);
    }


    public static function getSerializedAnimalsInBatchEditFormat(ControllerServiceBase $controllerServiceBase, array $animals = [], $includeLitter = false)
    {
        $foundUlns = [];
        $foundStns = [];

        $totalFoundAnimals = [];

        $jmsGroups = [JmsGroup::ANIMALS_BATCH_EDIT];
        if ($includeLitter) {
            $jmsGroups[] = JmsGroup::LITTER;
        }

        /** @var Animal $animal */
        foreach ($animals as $animal) {
            $serializedAnimal = $controllerServiceBase->getDecodedJsonOfAnimalWithParents(
                $animal,
                $jmsGroups,
                true,
                true
            );
            $totalFoundAnimals[] = $serializedAnimal;

            $uln = $animal->getPedigreeString();
            $foundStns[$uln] = $uln;

            $stn = $animal->getUln();
            $foundUlns[$stn] = $stn;
        }

        return [
            JsonInputConstant::ANIMALS =>  $totalFoundAnimals,
            JsonInputConstant::FOUND_ULNS => $foundUlns,
            JsonInputConstant::FOUND_STNS => $foundStns,
        ];
    }


    /**
     * @param Request $request
     * @return JsonResponse|bool
     */
    private function validateAnimalsByPlainTextInputRequest(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);

        if ($content === null) {
            return ResultUtil::errorResult($this->translateUcFirstLower('CONTENT IS MISSING.'), Response::HTTP_BAD_REQUEST);
        }

        $errorMessage = '';
        $errorMessagePrefix = '';

        if ($content->get(JsonInputConstant::PLAIN_TEXT_INPUT) === null) {
            $errorMessage .= $errorMessagePrefix . $this->translateUcFirstLower('THE PLAIN_TEXT_INPUT FIELD IS MISSING.');
            $errorMessagePrefix = ' ';
        }

        if ($content->get(JsonInputConstant::SEPARATOR) === null) {
            $errorMessage .= $errorMessagePrefix . $this->translateUcFirstLower('THE SEPARATOR FIELD IS MISSING.');
        }

        if ($errorMessage !== '') {
            return ResultUtil::errorResult($errorMessage, Response::HTTP_BAD_REQUEST);
        }

        return true;
    }


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

        $isEwesWithLastMate = RequestUtil::getBooleanQuery($request, QueryParameter::IS_EWES_WITH_LAST_MATE, false);;

        if ($isEwesWithLastMate) {
            $livestock = $this->getManager()->getRepository(Animal::class)
                ->getEwesLivestockWithLastMate($location, $this->getCacheService(), $this->getBaseSerializer(), true);
            $jmsGroups = AnimalRepository::getEwesLivestockWithLastMateJmsGroups();
            $jmsGroups[] = JmsGroup::IS_NOT_HISTORIC_ANIMAL;

        } else {
            $livestock = $this->getManager()->getRepository(Animal::class)
                ->getLiveStock($location, $this->getCacheService(), $this->getBaseSerializer(), true);
            $jmsGroups = [JmsGroup::LIVESTOCK, JmsGroup::IS_NOT_HISTORIC_ANIMAL];
        }

        $serializedLivestockAnimals = $this->getBaseSerializer()
            ->getDecodedJson($livestock, $jmsGroups);

        return ResultUtil::successResult($serializedLivestockAnimals);
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
            ->getHistoricLiveStock($location, $this->getCacheService(), $this->getBaseSerializer());

        $serializedHistoricLivestock = $this->getBaseSerializer()
            ->getDecodedJson($historicLivestock,[JmsGroup::LIVESTOCK, JmsGroup::IS_HISTORIC_ANIMAL]);

        return ResultUtil::successResult($serializedHistoricLivestock);
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
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    function getLatestRvoLeadingRetrieveAnimals(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $retrieveAnimals = $this->getManager()->getRepository(RetrieveAnimals::class)
            ->getLatestRvoLeadingRetrieveAnimals($location);

        $this->getBaseSerializer()->getDecodedJson($retrieveAnimals,[JmsGroup::BASIC]);

        return ResultUtil::successResult($retrieveAnimals);
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

        $isRvoLeading = $content !== null && $content->get(JsonInputConstant::IS_RVO_LEADING) === true;
        if ($isRvoLeading) {
            if (!AdminValidator::isAdmin($loggedInUser, AccessLevelType::SUPER_ADMIN)) {
                // Only a SuperAdmin is allowed to force an RVO Leading animal sync
                return ResultUtil::errorResult('Alleen een Superbeheerder (SuperAdmin) mag een RVO leidende dier sync melden', Response::HTTP_UNAUTHORIZED);
            }
        }

        //Convert the array into an object and add the mandatory values retrieved from the database
        $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $client, $loggedInUser, $location);

        //First Persist object to Database, before sending it to the queue
        $this->persist($messageObject);

        //Send it to the queue and persist/update any changed state to the database
        $messageArray = $this->sendMessageObjectToQueue($messageObject);

        if ($isRvoLeading) {
            AdminActionLogWriter::rvoLeadingAnimalSync($this->getManager(), $client, $messageObject);
        }

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

        $hasNotBeenSyncedForAtLeastThisAmountOfDays = RequestUtil::getIntegerQuery($request, QueryParameter::MAX_DAYS,7);
        $message = $this->syncAnimalsForAllLocations($admin, $hasNotBeenSyncedForAtLeastThisAmountOfDays)[Constant::MESSAGE_NAMESPACE];
        return ResultUtil::successResult($message);
    }


    /**
     * @param $loggedInUser
     * @param int $hasNotBeenSyncedForAtLeastThisAmountOfDays
     * @return array
     */
    public function syncAnimalsForAllLocations($loggedInUser, $hasNotBeenSyncedForAtLeastThisAmountOfDays = 0)
    {
        $allLocations = $this->getManager()->getRepository(Location::class)
            ->getLocationsNonSyncedLocations($hasNotBeenSyncedForAtLeastThisAmountOfDays);
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

        return array(Constant::MESSAGE_NAMESPACE => $message,
            Constant::COUNT => $count);
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
    public function getAnimalDetailsByUln(Request $request, $ulnString)
    {
        $isAdminEnvironment = RequestUtil::getBooleanQuery($request, JsonInputConstant::IS_ADMIN_ENV);

        if($isAdminEnvironment) {

            if(!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN))
            { return AdminValidator::getStandardErrorResponse(); }

            if (RequestUtil::getBooleanQuery($request, QueryParameter::MINIMAL_OUTPUT, false)) {
                return $this->getBasicAnimalDetailsByUln($ulnString);
            }

            $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnString);

            if($animal === null) {
                return ResultUtil::errorResult("No animal was found with uln: ".$ulnString, Response::HTTP_NOT_FOUND);
            }

            return $this->getAnimalDetailsOutputForAdminEnvironment($animal);
        }

        //VWA environment
        if ($this->getUser() instanceof VwaEmployee) {
            return $this->getBasicAnimalDetailsByUln($ulnString);
        }

        //User environment
        $isAdmin = AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN);

        $location = null;
        if(!$isAdmin) { $location = $this->getSelectedLocation($request); }

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $isAdmin, $location, $ulnString);
        if(!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }

        $animal = $animalDetailsValidator->getAnimal();

        if (RequestUtil::getBooleanQuery($request, QueryParameter::MINIMAL_OUTPUT, false)) {
            return $this->getBasicAnimalDetailsByUln($ulnString);
        }

        return $this->getAnimalDetailsOutputForUserEnvironment($animal);
    }


    /**
     * @param string $ulnString
     * @return JsonResponse
     */
    private function getBasicAnimalDetailsByUln($ulnString)
    {
        $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnString);
        if ($animal === null) {
            return ResultUtil::errorResult($this->translateUcFirstLower(AnimalDetailsValidator::ERROR_NON_EXISTENT_ANIMAL), Response::HTTP_BAD_REQUEST);
        }
        $output = $this->getBaseSerializer()->getDecodedJson($animal, [JmsGroup::BASIC]);
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