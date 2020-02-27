<?php


namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Controller\AnimalAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\EditType;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\VwaEmployee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\AnimalObjectType;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\EditTypeEnum;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\LiveStockQueryType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\RequestType;
use AppBundle\Output\AnimalDetailsOutput;
use AppBundle\Output\AnimalOutput;
use AppBundle\SqlView\View\ViewAnimalLivestockOverviewDetails;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\BreedCodeUtil;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AnimalService extends DeclareControllerServiceBase implements AnimalAPIControllerInterface
{
    const DEFAULT_ALL_SYNC_DELAY_IN_SECONDS = 30;

    /** @var AnimalDetailsOutput */
    private $animalDetailsOutput;

    /** @var ValidatorInterface */
    private $validator;

    /** @var AwsInternalQueueService */
    private $internalQueueService;

    /**
     * @required
     *
     * @param AnimalDetailsOutput $animalDetailsOutput
     */
    public function setAnimalDetailsOutput(AnimalDetailsOutput $animalDetailsOutput)
    {
        $this->animalDetailsOutput = $animalDetailsOutput;
    }

		/**
		 * @required
		 *
		 * @param ValidatorInterface $validator
		 */
		public function setValidator(ValidatorInterface $validator)
		{
			$this->validator = $validator;
		}

    /**
     * @required
     *
     * @param AwsInternalQueueService $internalQueueService
     */
    public function setInternalQueueService(AwsInternalQueueService $internalQueueService)
    {
        $this->internalQueueService = $internalQueueService;
    }

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
		 * @return JsonResponse
		 */
    public function createAnimal(Request $request)
    {
        $actionBy = $this->getUser();
        AdminValidator::isAdmin($actionBy, AccessLevelType::ADMIN, true);

        $animalArray = RequestUtil::getContentAsArrayCollection($request)->toArray();

        /** @var Neuter|Ram|Ewe $newAnimal */
        $tempNewAnimal = $this->getBaseSerializer()->denormalizeToObject($animalArray, Animal::class, false);

        switch ($tempNewAnimal->getGender()) {
            case GenderType::FEMALE: $clazz = Ewe::class; break;
            case GenderType::MALE: $clazz = Ram::class; break;
            default: throw new PreconditionFailedHttpException(
                'Gender must be '.GenderType::MALE.' OR '.GenderType::FEMALE);
        }
        $tempNewAnimal = null;
        $newAnimal = $this->getBaseSerializer()->denormalizeToObject($animalArray, $clazz, false);

        $uln = $newAnimal->getUln();
        if (!Validator::verifyUlnFormat($uln)) {
            throw new BadRequestHttpException('Dit is geen geldige ULN.');
        }

        $existingAnimal = $this->getManager()->getRepository(Animal::class)->findByUlnOrPedigree($uln, true);
        if (!empty($existingAnimal)) {
            throw new BadRequestHttpException('Dit dier bestaat al.');
        }

        if (empty($newAnimal->getDateOfBirth())) {
            throw new BadRequestHttpException('Vul een geboortedatum in.');
        }

        if ($newAnimal->getNLing() === '' || $newAnimal->getNLing() === null) {
            $newAnimal->setNLing(null);
        } elseif (!is_int($newAnimal->getNLing()) && !ctype_digit($newAnimal->getNLing())) {
            throw new BadRequestHttpException('n-Ling moet een integer zijn');
        } elseif ($newAnimal->getNLing() < Animal::MIN_N_LING_VALUE || Animal::MAX_N_LING_VALUE < $newAnimal->getNLing()) {
            throw new BadRequestHttpException($this->translateUcFirstLower('THE FOLLOWING N LINGS SHOULD HAVE A VALUE BETWEEN 0 AND 7').': '.$newAnimal->getNLing());
        }


        $newAnimal->getDateOfBirth()->setTime(0,0,0);

        // Set non nullable values
        $newAnimal->setAnimalType(AnimalType::sheep);
        $newAnimal->setAnimalCategory(Constant::DEFAULT_ANIMAL_CATEGORY);
        $newAnimal->setUpdatedGeneDiversity(false);
        $newAnimal->setCreationDate(new \DateTime());

        switch (true) {
            case $newAnimal instanceof Ram: $objectType = AnimalObjectType::Ram; break;
            case $newAnimal instanceof Ewe: $objectType = AnimalObjectType::Ewe; break;
            case $newAnimal instanceof Neuter: $objectType = AnimalObjectType::Neuter; break;
            default: $objectType = null; break;
        }
        $newAnimal->setObjectType($objectType);

        // Just use default values for now
        $newAnimal->setIsImportAnimal(false);
        $newAnimal->setIsExportAnimal(false);
        $newAnimal->setIsDepartedAnimal(false);

        $newAnimal = AnimalDetailsBatchUpdaterService::cleanUpAnimalInputValues($newAnimal);

        if (!BreedCodeUtil::isValidBreedCodeString($newAnimal->getBreedCode()) && $newAnimal->getBreedCode() !== null) {
            throw new BadRequestHttpException('Ongeldige rascode');
        }

        if (!Validator::hasValidBreedType($newAnimal->getBreedType(), true)) {
            throw new BadRequestHttpException('Ongeldige rastype');
        }

        try {
            $newAnimal->setAnimalOrderNumber(StringUtil::getLast5CharactersFromString($newAnimal->getUlnNumber()));
            $newAnimal = $this->setLocationOfBirth($newAnimal);
            $newAnimal = $this->setCurrentLocation($newAnimal);
            $newAnimal = $this->setStartAnimalResidence($newAnimal);

            $newAnimal = $this->setParents($newAnimal);
            if ($newAnimal instanceof JsonResponse) {
                return $newAnimal;
            }

            $newAnimal = $this->setPedigreeRegister($newAnimal);

            $this->getManager()->persist($newAnimal);
            $this->getManager()->flush();

            AnimalCacher::cacheByAnimalIds($this->getConnection(), [$newAnimal->getId()]);
        }
        catch(\Exception $e) {
            $this->logExceptionAsError($e);
            return ResultUtil::errorResult('INTERNAL SERVER ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            AdminActionLogWriter::createAnimal($this->getManager(), $actionBy, $newAnimal, $request->getContent());
        } catch (\Exception $t) {
            $this->logExceptionAsError($t);
        }

        if ($newAnimal->getLocation()) {
            $this->getCacheService()->clearLivestockCacheForLocation($newAnimal->getLocation(), $newAnimal);
        }

        $minimizedOutput = AnimalOutput::createAnimalArray($newAnimal, $this->getManager());
        return ResultUtil::successResult($minimizedOutput);
    }

    /**
     * @param Animal $animal
     * @return Animal
     */
    private function setLocationOfBirth(Animal $animal): Animal
    {
        $setEmptyLocationOfBirth = true;
        if ($animal->getLocationOfBirthId()) {
            $locationOfBirth = $this->getManager()->getRepository(Location::class)->findOneBy(['locationId' => $animal->getLocationOfBirthId()]);
            if ($locationOfBirth) {
                $animal->setLocationOfBirth($locationOfBirth);
                $setEmptyLocationOfBirth = false;
            }
        }

        if ($setEmptyLocationOfBirth) {
            $animal->setLocationOfBirth(null);
        }

        return $animal;
    }

    /**
     * @param Animal $animal
     * @return Animal
     */
    private function setCurrentLocation(Animal $animal): Animal
    {
        $setEmptyLocation = true;
        if ($animal->getLocation() && $animal->getLocation()->getLocationId()) {
            $location = $this->getManager()->getRepository(Location::class)->findOneBy(['locationId' => $animal->getLocation()->getLocationId()]);
            if ($location) {
                $animal->setLocation($location);
                $setEmptyLocation = false;
            }
        }

        if ($setEmptyLocation) {
            $animal->setLocation(null);
        }

        return $animal;
    }

    private function setStartAnimalResidence(Animal $animal)
    {
        $residences = new ArrayCollection();
        if ($animal->getAnimalResidenceHistory() && $animal->getAnimalResidenceHistory()->count() > 0) {
            $editType = $this->getManager()->getRepository(EditType::class)->getEditType(EditTypeEnum::ADMIN_CREATE);

            /** @var AnimalResidence $startResidence */
            $startResidence = $animal->getAnimalResidenceHistory()->first();
            $startResidence->getStartDate()->setTime(0,0,0);
            $startResidence->setStartDateEditedBy($this->getUser());
            $startResidence->setStartDateEditType($editType);
            $startResidence->setAnimal($animal);
            $startResidence->setIsPending(false);

            if ($startResidence->getLocation() && $startResidence->getLocation()->getLocationId()) {
                $location = $this->getManager()->getRepository(Location::class)->findOneBy(['locationId' => $startResidence->getLocation()->getLocationId()]);
                if ($location) {
                    $startResidence->setLocation($location);
                    $countryCode = $this->getManager()->getRepository(Location::class)->getCountryCode($location);
                    $startResidence->setCountry($countryCode);
                    $startResidence->setLogDate(new \DateTime());

                    $residences->add($startResidence);
                }
            }
        }
        $animal->setAnimalResidenceHistory($residences);
        return $animal;
    }

    /**
     * @param Animal $animal
     * @return Animal|JsonResponse
     */
    private function setParents(Animal $animal)
    {
        $isMotherYoungerThanChild = false;
        $isFatherYoungerThanChild = false;
        $removeMother = true;
        $removeFather = true;
        if ($animal->getParentMotherId()) {
            $mother = $this->getManager()->getRepository(Animal::class)->find($animal->getParentMotherId());
            if ($mother) {
                if ($mother->getDateOfBirth() > $animal->getDateOfBirth()) {
                    $isMotherYoungerThanChild = true;
                } else {
                    $animal->setParentMother($mother);
                    $removeMother = false;
                }
            }
        }

        if ($animal->getParentFatherId()) {
            $father = $this->getManager()->getRepository(Animal::class)->find($animal->getParentFatherId());
            if ($father) {
                if ($father->getDateOfBirth() > $animal->getDateOfBirth()) {
                    $isFatherYoungerThanChild = true;
                } else {
                    $animal->setParentFather($father);
                    $removeFather = false;
                }
            }
        }

        if ($removeMother) {
            $animal->setParentMother(null);
        }

        if ($removeFather) {
            $animal->setParentFather(null);
        }

        if ($isMotherYoungerThanChild || $isFatherYoungerThanChild) {
            $errorMessage = $isMotherYoungerThanChild ? $this->translateUcFirstLower('MOTHER CANNOT BE YOUNGER THAN CHILD') . '. ' : '';
            $errorMessage .= $isFatherYoungerThanChild ? $this->translateUcFirstLower('FATHER CANNOT BE YOUNGER THAN CHILD') . '. ' : '';
            return ResultUtil::errorResult($errorMessage,Response::HTTP_PRECONDITION_REQUIRED);
        }

        return $animal;
    }

    /**
     * @param Animal $animal
     * @return Animal
     */
    private function setPedigreeRegister(Animal $animal): Animal
    {
        $removeRegister = true;
        if ($animal->getPedigreeRegister() && $animal->getPedigreeRegister()->getId()) {
            $pedigreeRegister = $this->getManager()->getRepository(PedigreeRegister::class)
                ->find($animal->getPedigreeRegister()->getId());
            $animal->setPedigreeRegister($pedigreeRegister);
            $removeRegister = false;
        }

        if ($removeRegister) {
            $animal->setPedigreeRegister(null);
        }

        return $animal;
    }

    public function findAnimal(Request $request)
    {
        AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN, true);

        $data = RequestUtil::getContentAsArrayCollection($request);
        $uln = $data->get('uln');
        if (is_string($uln)) {
            $uln = strtoupper(strtr($uln, [' ' => '']));
        }
        if (!Validator::verifyUlnFormat($uln)) {
            throw new BadRequestHttpException('Dit is geen geldige ULN.');
        }

        try {
            $animal = $this->getManager()->getRepository(Animal::class)->findByUlnOrPedigree($uln, true);
            if (!$animal) {
                throw new BadRequestHttpException('Dit dier bestaat niet.');
            }

            $minimizedOutput = AnimalOutput::createAnimalArray($animal, $this->getManager());
            return ResultUtil::successResult($minimizedOutput);
        }
        catch (\Exception $e){
            return ResultUtil::successResult($e);
        }
    }


    private function getUbnsFromPlainTextInput(ArrayCollection $content) {
        $ubns = [];
        if ($content->containsKey(JsonInputConstant::UBNS)) {
            $ubns = $content->get(JsonInputConstant::UBNS);
        }
        return $ubns;
    }


    /**
     * @param Request $request
     * @return JsonResponse|bool
     */
    private function getAnimalsByPlainTextInput(Request $request)
    {
        AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN, true);

        $validationResult = $this->validateAnimalsByPlainTextInputRequest($request);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        $content = RequestUtil::getContentAsArrayCollection($request);
        $plainTextInput = StringUtil::preparePlainTextInput($content->get(JsonInputConstant::PLAIN_TEXT_INPUT));
        $separator = $content->get(JsonInputConstant::SEPARATOR);

        $ubns = $this->getUbnsFromPlainTextInput($content);

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

    /**
     * @param ControllerServiceBase $controllerServiceBase
     * @param array $animals
     * @param bool $includeLitter
     * @return array
     */
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
        $content = RequestUtil::getContentAsArrayCollection($request);

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
        if (ctype_digit($uln) || is_int($uln)) {
            $animal = $this->getManager()->getRepository(Animal::class)->find(intval($uln));
        } else {
            $animal = $this->getManager()->getRepository(Animal::class)->findByUlnOrPedigree($uln, true);
        }

        $minimizedOutput = AnimalOutput::createAnimalArray($animal, $this->getManager());
        return new JsonResponse($minimizedOutput, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLiveStock(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        $type = $request->query->get(QueryParameter::TYPE_QUERY);
        $type = is_string($type) ? strtolower($type) : null;

        $isEwesWithLastMate = false;
        $isLivestockWithLastWeight = false;
        switch ($type) {
            case LiveStockQueryType::EWES_WITH_LAST_MATE; $isEwesWithLastMate = true; break;
            case LiveStockQueryType::LAST_WEIGHT; $isLivestockWithLastWeight = true; break;
            default; break;
        }

        $filterLivestockByGenderQueryParam = true;

        if ($isEwesWithLastMate) {
            $livestock = $this->getManager()->getRepository(Animal::class)
                ->getEwesLivestockWithLastMate($location, $this->getCacheService(), $this->getBaseSerializer(), true);
            $jmsGroups = AnimalRepository::getEwesLivestockWithLastMateJmsGroups();
            $jmsGroups[] = JmsGroup::IS_NOT_HISTORIC_ANIMAL;
            $filterLivestockByGenderQueryParam = false;

        } else if ($isLivestockWithLastWeight) {
            $livestock = $this->getManager()->getRepository(Animal::class)
                ->getLivestockWithLastWeight($location, $this->getCacheService(), $this->getBaseSerializer(), true);
            $jmsGroups = AnimalRepository::getLivestockWithLastWeightJmsGroups();
            $jmsGroups[] = JmsGroup::IS_NOT_HISTORIC_ANIMAL;

        } else {
            $livestock = $this->getManager()->getRepository(Animal::class)
                ->getLiveStock($location, $this->getCacheService(), $this->getBaseSerializer(), true);
            $jmsGroups = [JmsGroup::LIVESTOCK, JmsGroup::IS_NOT_HISTORIC_ANIMAL];
        }

        if ($filterLivestockByGenderQueryParam) {
            $gender = $request->query->get(QueryParameter::GENDER);
            $livestock = $this->filterLivestockByGenderQueryParam($livestock, $gender);
        }

        $serializedLivestockAnimals = $this->getBaseSerializer()
            ->getDecodedJson($livestock, $jmsGroups);

        return ResultUtil::successResult($serializedLivestockAnimals);
    }


    /**
     * @param Animal[] $animals
     * @param string $genderQueryParam
     * @return Animal[]
     */
    private function filterLivestockByGenderQueryParam($animals, $genderQueryParam)
    {
        if (!is_string($genderQueryParam)) {
            return $animals;
        }

        $genderQueryParam = strtoupper($genderQueryParam);

        if (
            $genderQueryParam !== GenderType::MALE &&
            $genderQueryParam !== GenderType::FEMALE &&
            $genderQueryParam !== GenderType::NEUTER
        ) {
            return $animals;
        }

        /**
         * @var int $key
         * @var Animal $animal
         */
        foreach ($animals as $key => $animal) {
            if ($animal->getGender() !== $genderQueryParam) {
                unset($animals[$key]);
            }
        }

        return $animals;
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

        $gender = $request->query->get(QueryParameter::GENDER);
        $historicLivestock = $this->filterLivestockByGenderQueryParam($historicLivestock, $gender);

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
     * @throws \Exception
     */
    public function createRetrieveAnimals(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        if($client == null) { return ResultUtil::errorResult('Client cannot be null', 428); }
        if($location == null) { return ResultUtil::errorResult('Location cannot be null', 428); }

        if (!$location->isDutchLocation()) {
            throw new PreconditionFailedHttpException('RVO animal sync cannot be executed for non-NL UBN');
        }

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
     * @throws \Exception
     */
    public function createRetrieveAnimalsForAllLocations(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::DEVELOPER)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $hasNotBeenSyncedForAtLeastThisAmountOfDays = RequestUtil::getIntegerQuery($request, QueryParameter::MAX_DAYS,7);
        $delayInSeconds = RequestUtil::getIntegerQuery($request, QueryParameter::DELAY_IN_SECONDS,self::DEFAULT_ALL_SYNC_DELAY_IN_SECONDS);
        $isRvoLeading = RequestUtil::getBooleanQuery($request, QueryParameter::IS_RVO_LEADING,false);
        $maxOnceADay = RequestUtil::getBooleanQuery($request, QueryParameter::IS_MAX_ONCE_A_DAY,true);

        $message = $this->syncAnimalsForAllLocations($admin, $hasNotBeenSyncedForAtLeastThisAmountOfDays,
            $isRvoLeading, $delayInSeconds, $maxOnceADay)[Constant::MESSAGE_NAMESPACE];

        return ResultUtil::successResult($message);
    }

    /**
     * @param $loggedInUser
     * @param int $hasNotBeenSyncedForAtLeastThisAmountOfDays
     * @param bool $isRvoLeading
     * @param int $delayInSeconds
     * @param int $maxInternalQueueSize
     * @param boolean $maxLimitOnceADay
     * @return array
     * @throws \Exception
     */
    public function syncAnimalsForAllLocations($loggedInUser,
                                               $hasNotBeenSyncedForAtLeastThisAmountOfDays = 0,
                                               bool $isRvoLeading = false,
                                               int $delayInSeconds = 5,
                                               bool $maxLimitOnceADay = true,
                                               int $maxInternalQueueSize = 10
    )
    {
        $allLocations = $this->getManager()->getRepository(Location::class)
            ->getLocationsNonSyncedLocations($hasNotBeenSyncedForAtLeastThisAmountOfDays, $isRvoLeading, $maxLimitOnceADay);
        $content = new ArrayCollection();
        $count = 0;

        if (empty($delayInSeconds)) {
            $this->getLogger()->notice('Consecutively send all sync messages without any delay in between');
        }

        $totalLocationsCount = empty($allLocations) ? 0 : count($allLocations);
        $counter = 0;

        /** @var Location $location */
        foreach($allLocations as $location)
        {
            $this->sleepIfInternalQueueIsTooFull($maxInternalQueueSize);

            $counter++;
            if (
                (!$location->getIsActive() && !$location->getCompany()->isActive()) ||
                !$location->isDutchLocation()
            ) {
                continue;
            }

            $client = $location->getCompany()->getOwner();

            if (empty($client->getRelationNumberKeeper())) {
                $this->getLogger()->error('Animal Sync failed due to missing relationNumberKeeper for UBN: '.
                $location->getUbn().', ownerId: '.$client->getId());
                continue;
            }

            //Convert the array into an object and add the mandatory values retrieved from the database
            /** @var RetrieveAnimals $messageObject */
            $messageObject = $this->buildMessageObject(RequestType::RETRIEVE_ANIMALS_ENTITY, $content, $client, $loggedInUser, $location);
            $messageObject->setIsRvoLeading($isRvoLeading);

            //First Persist object to Database, before sending it to the queue
            $this->persist($messageObject);

            //Send it to the queue and persist/update any changed state to the database
            $messageArray = $this->sendMessageObjectToQueue($messageObject);

            $this->getLogger()->notice($location->getUbn()
                . ' ' . ($isRvoLeading ? '(RVO LEADING)' : '(NSFO LEADING)')
                . ' SYNC sent to queue '.'[ '.$counter.' of '.$totalLocationsCount.' ]'
            );

            $count++;

            if (!empty($delayInSeconds)) {
                $this->getLogger()->notice('Sleep '.$delayInSeconds.' seconds ...');
                sleep($delayInSeconds);
            }
        }

        $total = sizeof($allLocations);
        $message = "THE ANIMALS HAVE BEEN SYNCED FOR " . $count . " OUT OF " . $total . " TOTAL LOCATIONS (UBNS)";

        return array(Constant::MESSAGE_NAMESPACE => $message,
            Constant::COUNT => $count);
    }


    /**
     * @param int $maxInternalQueueSize deactivate queue size check for 0 or less
     * @param int $delayInSecondsForFullQueue
     */
    private function sleepIfInternalQueueIsTooFull(int $maxInternalQueueSize = 0, int $delayInSecondsForFullQueue = 120)
    {
        if ($maxInternalQueueSize < 1) {
            return;
        }
        while ($this->internalQueueService->getSizeOfQueue() > $maxInternalQueueSize) {
            $this->getLogger()->notice('InternalQueue has more than '.$maxInternalQueueSize.' messages. Sleep for '.$delayInSecondsForFullQueue.' seconds ...');
            sleep($delayInSecondsForFullQueue);
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    function createAnimalDetails(Request $request)
    {
        //Get content to array
        $content = RequestUtil::getContentAsArrayCollection($request);
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
     * @param string $ulnStringOrId
     * @return JsonResponse
     * @throws \Exception
     */
    public function getAnimalDetailsByUlnOrId(Request $request, $ulnStringOrId)
    {
        $isAdminEnvironment = RequestUtil::getBooleanQuery($request, JsonInputConstant::IS_ADMIN_ENV);

        if($isAdminEnvironment) {

            if(!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN))
            { throw AdminValidator::standardException(); }

            if (RequestUtil::getBooleanQuery($request, QueryParameter::MINIMAL_OUTPUT, false)) {
                return $this->getBasicAnimalDetailsByUlnOrId($ulnStringOrId);
            }

            $animal = $this->findAnimalByUlnStringOrId($ulnStringOrId);
            return $this->getAnimalDetailsOutputForAdminEnvironment($animal);
        }

        //VWA environment
        if ($this->getUser() instanceof VwaEmployee) {
            return $this->getBasicAnimalDetailsByUlnOrId($ulnStringOrId);
        }

        //User environment
        $isAdmin = AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN);

        $location = null;
        if(!$isAdmin) { $location = $this->getSelectedLocation($request); }

        $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $this->getSqlViewManager(), $isAdmin, $location, $ulnStringOrId);
        if(!$animalDetailsValidator->getIsInputValid()) {
            return $animalDetailsValidator->createJsonResponse();
        }

        $animal = $animalDetailsValidator->getAnimal();

        if (RequestUtil::getBooleanQuery($request, QueryParameter::MINIMAL_OUTPUT, false)) {
            return $this->getBasicAnimalDetailsByUlnOrId($ulnStringOrId);
        }

        return ResultUtil::successResult(
            $this->animalDetailsOutput->getForUserEnvironment(
                $animal,
                $this->getUser(),
                $location
            )
        );
    }

    private function findAnimalByUlnStringOrId($ulnStringOrId): Animal {
        if (ctype_digit($ulnStringOrId) || is_int($ulnStringOrId)) {
            $animalId = intval($ulnStringOrId);
            $animal = $this->getManager()->getRepository(Animal::class)->find($animalId);
        } else {
            $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnStringOrId);
        }
        if (!$animal) {
            $errorMessage = $this->translateUcFirstLower(AnimalDetailsValidator::ERROR_NON_EXISTENT_ANIMAL);
            throw new NotFoundHttpException($errorMessage);
        }
        return $animal;
    }

    /**
     * @param Request $request
     * @param string $ulnString
     * @return JsonResponse
     * @throws \Exception
     */
    public function getChildrenByUln(Request $request, $ulnString)
    {
        //VWA environment
        if ($this->getUser() instanceof VwaEmployee) {
            throw AdminValidator::standardException();
        }

        //Admin environment
        $location = null;
        $isAdminEnvironment = RequestUtil::getBooleanQuery($request, JsonInputConstant::IS_ADMIN_ENV);
        if($isAdminEnvironment) {
            if(!AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN))
            { throw AdminValidator::standardException(); }

            $animal = $this->getManager()->getRepository(Animal::class)->findAnimalByUlnString($ulnString);
            if($animal === null) {
                return ResultUtil::errorResult("No animal was found with uln: ".$ulnString, Response::HTTP_NOT_FOUND);
            }

        } else {
            //User environment
            $isAdmin = AdminValidator::isAdmin($this->getEmployee(), AccessLevelType::ADMIN);

            $location = null;
            if(!$isAdmin) { $location = $this->getSelectedLocation($request); }

            $animalDetailsValidator = new AnimalDetailsValidator($this->getManager(), $this->getSqlViewManager(), $isAdmin, $location, $ulnString);
            if(!$animalDetailsValidator->getIsInputValid()) {
                return $animalDetailsValidator->createJsonResponse();
            }

            $animal = $animalDetailsValidator->getAnimal();
        }

        return ResultUtil::successResult(
            $this->animalDetailsOutput->getChildrenOutput(
                $animal,
                $this->getUser(),
                $location
            )
        );
    }

    /**
     * @param string $idOrUlnString
     * @return JsonResponse
     */
    private function getBasicAnimalDetailsByUlnOrId($idOrUlnString)
    {
        $animal = $this->findAnimalByUlnStringOrId($idOrUlnString);
        $output = $this->getBaseSerializer()->getDecodedJson($animal, [JmsGroup::BASIC]);
        return ResultUtil::successResult($output);
    }

    /**
     * @param Request $request
     * @return JsonResponse|Animal|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function changeGenderOfUln(Request $request)
    {
        $content = RequestUtil::getContentAsArrayCollection($request);
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
        $targetObjectType = null;
        $result = null;

        switch ($gender) {
            case AnimalObjectType::EWE:
                $targetGender = "FEMALE";
                $targetObjectType = AnimalObjectType::Ewe;
                $result = $genderChanger->changeToGender($animal, Ewe::class, $this->getUser());
                break;
            case AnimalObjectType::RAM:
                $targetGender = "MALE";
                $targetObjectType = AnimalObjectType::Ram;
                $result = $genderChanger->changeToGender($animal, Ram::class, $this->getUser());
                break;
            case AnimalObjectType::NEUTER:
                $targetGender = "NEUTER";
                $targetObjectType = AnimalObjectType::Neuter;
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

        //FIXME Temporarily workaround
        $minimizedOutput['type'] = $targetObjectType;

        return new JsonResponse($minimizedOutput, 200);
    }

	/**
	 * @param Request $request
	 * @param Ram|Ewe|Neuter|Animal $animal
	 * @return array|null
	 */
		public function changeNicknameOfAnimal(Request $request, Animal $animal)
		{
				$content = RequestUtil::getContentAsArrayCollection($request);

				//Check if mandatory field values are given
				if(!$content->containsKey(ReportLabel::NICKNAME)) {
				    throw new BadRequestHttpException(ReportLabel::NICKNAME.' is missing.');
				}

				//Try to change animal gender
				$nickname = $content->get(ReportLabel::NICKNAME);
				$animal->setNickname($nickname);

				$errors = $this->validator->validate($animal);
				Validator::throwExceptionWithFormattedErrorMessageIfHasErrors($errors);

				$manager = $this->getManager();
				$manager->persist($animal);
				$manager->flush();

				$minimizedOutput = AnimalOutput::createAnimalArray($animal, $manager);

				//FIXME Temporarily workaround
				$minimizedOutput['type'] = $animal->getObjectType();
				return $minimizedOutput;
		}
}
