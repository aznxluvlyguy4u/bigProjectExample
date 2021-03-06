<?php

namespace AppBundle\Service;

use AppBundle\Cache\GeneDiversityUpdater;
use AppBundle\Cache\ProductionCacher;
use AppBundle\Component\MessageBuilderBase;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Client;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareImport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Entity\DeclareTagsTransfer;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Person;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RetrieveAnimalDetails;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\RetrieveTags;
use AppBundle\Entity\RetrieveUbnDetails;
use AppBundle\Entity\RevokeDeclaration;
use AppBundle\Entity\Stillborn;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Enumerator\TagType;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\BreedCodeUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Serializer;

/**
 * Class IRSerializer.
 *
 * There is an issue with the JMS Deserializing process, when we want to deserialize with an entity that has an
 * abstract base class with join table inheritance, the discriminator-type cannot be inferred/determined
 * during deserialization to an object, the data for the discriminator maps is lost.
 * This Serializer class adds the 'type' of the object to be deserialized explicitly to a
 * content array, which in turn can be serialized to JSON and then be deserialized to a given Entity
 * Though there is a 'Discriminator' annotation, see http://jmsyst.com/libs/serializer/master/reference/annotations#discriminator &
 * https://gist.github.com/h4cc/8313723 & for letting the (de)serializer know the base class, it does not work.
 * See issue: https://github.com/schmittjoh/JMSSerializerBundle/issues/292 & https://github.com/schmittjoh/JMSSerializerBundle/issues/299
 * Note that there has been effort of fixing the discriminator problem, a pull request
 * with a solution has already been opened, but up till now no communication or has been given on the given
 * pull request, see https://github.com/schmittjoh/serializer/pull/382
 * Therefore we need to customize the serializer to deal with this, thus this class will handle custom steps needed before the deserializing process.
 *
 * @package AppBundle\Service
 */
class IRSerializer extends BaseSerializer implements IRSerializerInterface
{
    const DISCRIMINATOR_TYPE_NAMESPACE = "type";

    /** @var ObjectManager */
    private $entityManager;
    /** @var \AppBundle\Service\EntityGetter */
    private $entityGetter;
    /** @var PedigreeDataGenerator */
    private $pedigreeDataGenerator;

    /** @var Connection */
    private $conn;

    public function __construct(Serializer $jmsSerializer,
                                EntityManagerInterface $entityManager,
                                EntityGetter $entityGetter,
                                PedigreeDataGenerator $pedigreeDataGenerator)
    {
        parent::__construct($jmsSerializer);

        $this->entityManager = $entityManager;
        $this->entityGetter = $entityGetter;
        $this->conn = $entityManager->getConnection();
        $this->pedigreeDataGenerator = $pedigreeDataGenerator;
    }

    /**
     * @return ObjectManager|EntityManagerInterface
     */
    public function getManager()
    {
        return $this->entityManager;
    }


    /**
     * @param Animal $retrievedAnimal
     * @param boolean $unsetChildren
     * @return array
     */
    function returnAnimalArray(Animal $retrievedAnimal, $unsetChildren = true)
    {
        //Parse to json
        $retrievedAnimalJson = $this->serializeToJSON($retrievedAnimal);
        //Parse json to content array to add additional 'animal type' property
        $retrievedAnimalContentArray = json_decode($retrievedAnimalJson, true);

        if($retrievedAnimal instanceof Ram) {
            $retrievedAnimalContentArray['type'] = 'Ram';
        }

        if($retrievedAnimal instanceof Ewe) {
            $retrievedAnimalContentArray['type'] = 'Ewe';
        }

        if($retrievedAnimal instanceof Neuter) {
            $retrievedAnimalContentArray['type'] = 'Neuter';
        }

        if($unsetChildren) {
            unset($retrievedAnimalContentArray[Constant::CHILDREN_NAMESPACE]);
            unset($retrievedAnimalContentArray[Constant::SURROGATE_CHILDREN_NAMESPACE]);
        }

        return  $retrievedAnimalContentArray;
    }

    /**
     * @param Animal $retrievedAnimal
     * @return array
     */
    function returnAnimalArrayIncludingParentsAndSurrogate(Animal $retrievedAnimal)
    {
        $childContentArray = $this->returnAnimalArray($retrievedAnimal);

        $childContentArray['parent_father'] = $this->returnAnimalArray($retrievedAnimal->getParentFather());
        $childContentArray['parent_mother'] =  $this->returnAnimalArray($retrievedAnimal->getParentMother());
        $childContentArray[JsonInputConstant::SURROGATE_MOTHER] =  $this->returnAnimalArray($retrievedAnimal->getSurrogate());

        return  $childContentArray;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareArrival(ArrayCollection $declareArrivalContentArray, Client $client, Location $location, $isEditMessage)
    {
        $declareArrivalContentArray["type"] = RequestType::DECLARE_ARRIVAL_ENTITY;

        //Retrieve animal entity
        if($isEditMessage) {
            $requestId = $declareArrivalContentArray['request_id'];
            $location = $declareArrivalContentArray[Constant::LOCATION_NAMESPACE];
            /** @var DeclareArrival $declareArrivalRequest */
            $declareArrivalRequest = $this->entityManager->getRepository(Constant::DECLARE_ARRIVAL_REPOSITORY)->getArrivalByRequestId($location, $requestId);
            $requestState = $declareArrivalRequest->getRequestState();

            //Update values here
            $declareArrivalRequest->setArrivalDate(new \DateTime($declareArrivalContentArray['arrival_date']));
            $ubnPreviousOwner = $declareArrivalContentArray['ubn_previous_owner'];
            $declareArrivalRequest->setUbnPreviousOwner($ubnPreviousOwner);

            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareArrivalRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareArrivalRequest->getResponses());
                if($lastResponse != null) {
                   $declareArrivalRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareArrivalRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
            $declareArrivalRequest->setRequestState(RequestStateType::OPEN);

        } else {
            $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareArrivalContentArray);
            $retrievedAnimal->setIsImportAnimal(false);

            $declareArrivalRequest = new DeclareArrival();
            $declareArrivalRequest->setAnimal($retrievedAnimal);
            $declareArrivalRequest->setArrivalDate(new \DateTime($declareArrivalContentArray['arrival_date']));
            $declareArrivalRequest->setUbnPreviousOwner($declareArrivalContentArray['ubn_previous_owner']);
            $declareArrivalRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));
            $declareArrivalRequest->setIsArrivedFromOtherNsfoClient($declareArrivalContentArray->get(JsonInputConstant::IS_ARRIVED_FROM_OTHER_NSFO_CLIENT));
            $declareArrivalRequest->setIsImportAnimal(false);

            $contentAnimal = $declareArrivalContentArray['animal'];

            if($contentAnimal != null) {

                if(array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareArrivalRequest->setUlnCountryCode($contentAnimal[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
                    $declareArrivalRequest->setUlnNumber($contentAnimal[Constant::ULN_NUMBER_NAMESPACE]);
                }

                if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareArrivalRequest->setPedigreeCountryCode($contentAnimal[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE]);
                    $declareArrivalRequest->setPedigreeNumber($contentAnimal[Constant::PEDIGREE_NUMBER_NAMESPACE]);
                }
            }
        }

        return $declareArrivalRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareBirth(ArrayCollection $declareBirthContentArray,
                               Client $client,
                               Person $loggedInUser,
                               Location $location,
                               $isEditMessage)
    {
        $father = null;
        $mother = null;
        $dateOfBirth = null;
        $isAborted = false;
        $isPseudoPregnancy = false;
        $childrenContent = [];
        $litterSize = 0;
        $stillbornCount = 0;
        $statusCode = 428;
        $declareBirthRequests = [];

        /** @var TagRepository $tagRepository */
        $tagRepository = $this->entityManager->getRepository(Tag::class);
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->entityManager->getRepository(Animal::class);
        
        if(key_exists('date_of_birth', $declareBirthContentArray->toArray())) {
            $dateOfBirth = new \DateTime($declareBirthContentArray["date_of_birth"]);

            //Disallow birth registrations in the future
            if(TimeUtil::isDateInFuture($dateOfBirth)) {
                return Validator::createJsonResponse("Een geboorte mag niet in de toekomst liggen.", $statusCode);
            }
        }

        if(key_exists('is_aborted', $declareBirthContentArray->toArray())) {
            $isAborted = $declareBirthContentArray["is_aborted"];
        }

        if(key_exists('is_pseudo_pregnancy', $declareBirthContentArray->toArray())) {
            $isPseudoPregnancy = $declareBirthContentArray["is_pseudo_pregnancy"];
        }

        if(key_exists('litter_size', $declareBirthContentArray->toArray())) {
            $litterSize = $declareBirthContentArray["litter_size"];
        }

        if(key_exists('stillborn_count', $declareBirthContentArray->toArray())) {
            $stillbornCount = $declareBirthContentArray["stillborn_count"];
        }

        if(key_exists('father', $declareBirthContentArray->toArray())) {
            /** @var Ram $father */
            $father = $animalRepository->getAnimalByUlnOrPedigree($declareBirthContentArray["father"]);

            if(!$father) {
                return Validator::createJsonResponse("Opgegeven vader kan niet gevonden worden.", $statusCode);
            }

            //Additional gender check
            if($father->getGender() != GenderType::MALE) {
                return Validator::createJsonResponse("Opgegeven vader met ULN: " . $father->getUlnNumber() ." is gevonden, echter is het geslacht, niet van het type: RAM.", $statusCode);
            }

            //Check date of birth of father
            if ($father->getDateOfBirth() === null) {
                return Validator::createJsonResponse("De geboortedatum ontbreekt van vader: " . $father->getUlnNumber() .".", $statusCode);
            }

            if (!BirthService::isFatherDateOfBirthValid($father, $dateOfBirth)) {
                return Validator::createJsonResponse("De vader moet minstens ".BirthService::MIN_FATHER_AGE_AT_BIRTH_IN_MONTHS." maanden oud zijn op het moment van de geboorte.", $statusCode);
            }
        }

        if(key_exists('mother', $declareBirthContentArray->toArray())) {
            /** @var Ewe $mother */
            $mother = $animalRepository->getAnimalByUlnOrPedigree($declareBirthContentArray["mother"]);

            if(!$mother) {
                return Validator::createJsonResponse("Opgegeven moeder kan niet gevonden worden.", $statusCode);
            }

            //Additional Gender check
            if($mother->getGender() != GenderType::FEMALE) {
                return Validator::createJsonResponse("Opgegeven moeder met ULN: " . $mother->getUlnNumber() ." is gevonden, echter is het geslacht, niet van het type: OOI.", $statusCode);
            }

            //If the mother already has given birth within the last 5,5 months (167 days, rounded),
            //disallow birth registration
            $maxDaysLitterInterval = 167;
            $litters = $mother->getLitters();

              /** @var Litter $litter */
            foreach ($litters as $litter) {
                if($litter->getStatus() != 'REVOKED') {
                  $dateInterval = abs(TimeUtil::getDaysBetween($litter->getLitterDate(), $dateOfBirth));

                  if($dateInterval <= $maxDaysLitterInterval) {
                    return Validator::createJsonResponse("Opgegeven moeder met ULN: "
                      . $mother->getUlnNumber()
                      ." heeft in de afgelopen 5,5 maanden reeds geworpen, zodoende is het niet geoorloofd om een geboortemelding te doen voor de opgegeven moeder.", $statusCode);
                  }
                }
            }
        }

        if(key_exists('children', $declareBirthContentArray->toArray())) {
            $childrenContent = $declareBirthContentArray["children"];

            //Disallow birth registration for litterSize bigger then 7 if a mother is given and found.
            //If no mother is given, allow arbitrary litter size.
            $maxLitterSize = 7;

            if($mother && $litterSize > $maxLitterSize) {
                return Validator::createJsonResponse("De opgegeven worpgrootte overschrijdt het maximum van " . $maxLitterSize ." lammeren", $statusCode);
            }
        }


        //Validate tags

        //First group ulns and check for duplicate ulns
        $usedTagUlns = [];
        foreach ($childrenContent as $childArray) {
            $isAlive = ArrayUtil::get('is_alive', $childArray);
            if ($isAlive) {
                if (key_exists(JsonInputConstant::ULN_COUNTRY_CODE, $childArray) && key_exists(JsonInputConstant::ULN_NUMBER, $childArray)) {
                    $ulnCountryCode = $childArray['uln_country_code'];
                    $ulnNumber = $childArray['uln_number'];
                    $uln = $ulnCountryCode.$ulnNumber;
                    if(key_exists($uln, $usedTagUlns)) {
                        return Validator::createJsonResponse('Oormerk '.$uln.' werd aan meer dan 1 kind toegekend.', $statusCode);
                    }
                    $usedTagUlns[$uln] = [
                        JsonInputConstant::ULN_COUNTRY_CODE => $ulnCountryCode,
                        JsonInputConstant::ULN_NUMBER => $ulnNumber,
                    ];
                }

                $gender = ArrayUtil::get('gender', $childArray, null);
                if (
                    $gender !== GenderType::MALE &&
                    $gender !== GenderType::FEMALE &&
                    $gender !== GenderType::NEUTER
                ) {
                    return Validator::createJsonResponse('Het geslacht ontbreekt voor een levendgeboren kind', $statusCode);
                }
            }
        }

        //Check if tags are in database and UNASSIGNED
        $tags = [];
        foreach ($usedTagUlns as $ulnArray) {
            $ulnCountryCode = $ulnArray[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber = $ulnArray[JsonInputConstant::ULN_NUMBER];
            $uln = $ulnCountryCode.$ulnNumber;

            /** @var Tag $tagToReserve */
            $tagToReserve = $tagRepository->findUnassignedTagByUlnNumberAndCountryCode($ulnCountryCode, $ulnNumber, $location->getId());

            if (!$tagToReserve) {
                //Tag does not exist in the database
                return Validator::createJsonResponse("Opgegeven vrije oormerk: " . $uln . " voor het lam, is niet gevonden.", $statusCode);

            } else {
                $animal = $animalRepository->findByUlnCountryCodeAndNumber($ulnCountryCode, $ulnNumber);
                if ($animal) {
                    return Validator::createJsonResponse("Opgegeven vrije oormerk: " . $uln . " voor het lam, is reeds toegewezen aan een bestaand dier met ULN: " . $uln, $statusCode);
                } else if ($tagToReserve->getLocation() && $tagToReserve->getLocation()->getId() == $location->getId()) {
                    $tags[$uln] = $tagToReserve;
                    continue;
                }
                return Validator::createJsonResponse("Opgegeven oormerk: " . $uln . " is niet geregistreerd voor dit UBN: " . $location->getUbn(), $statusCode);
            }
        }

        //tailLength & birthWeight are not nullable in DeclareWeight
        $tailLengthEmptyValue = 0;
        $birthWeightEmptyValue = 0;

        //Validate Surrogate Mothers and BirthWeight
        $surrogateMothersByUln = [];
        foreach ($childrenContent as $childArray) {
            $surrogate = null;
            if(array_key_exists(JsonInputConstant::SURROGATE_MOTHER, $childArray)) {

                if($this->hasLambar($childArray)) {
                    return Validator::createJsonResponse("Als een pleegmoeder is opgegeven mag het kind geen lambar hebben.", $statusCode);
                }

                /** @var Animal $surrogate */
                $surrogate = $animalRepository->getAnimalByUlnOrPedigree($childArray[JsonInputConstant::SURROGATE_MOTHER]);

                if(!$surrogate) {
                    return Validator::createJsonResponse("Opgegeven pleegmoeder kan niet gevonden worden.", $statusCode);
                }

                if($surrogate->getGender() != GenderType::FEMALE) {
                    return Validator::createJsonResponse("Opgegeven pleegmoeder met ULN: " .$surrogate->getUlnNumber() ." is gevonden, echter is het geslacht, niet van het type: OOI.", $statusCode);
                }

                if($surrogate->getId() == $mother->getId()) {
                    return Validator::createJsonResponse("Opgegeven pleegmoeder mag niet gelijk zijn aan de opgegeven moeder", $statusCode);
                }

                $surrogateMothersByUln[$surrogate->getUln()] = $surrogate;
            }


            $birthWeightValue = ArrayUtil::get('birth_weight', $childArray, $birthWeightEmptyValue);

            if($birthWeightValue > 9.9) {
                return Validator::createJsonResponse("Een lam met een geboortegewicht groter dan 9,9 kilogram, is niet geoorloofd.", $statusCode);
            }
        }

        /*
         * Before creating any birth related entities check their sequences and update them if they are incorrect
         * to prevent a UniqueConstraintViolationException
         */
        DoctrineUtil::updateTableSequence($this->conn, ['animal', 'animal_residence', 'tag', 'litter', 'declare_base']);

        //Create Litter
        $litter = new Litter();
        $litter->setLitterDate($dateOfBirth);
        $litter->setIsAbortion($isAborted);
        $litter->setIsPseudoPregnancy($isPseudoPregnancy);

        if ($isAborted || $isPseudoPregnancy || $litterSize == $stillbornCount) {
            $litter->setFinishedStatus();
        } else {
            $litter->setOpenStatus();
        }

        $litter->setActionBy($loggedInUser);
        $litter->setRelationNumberKeeper($location->getCompany()->getOwner()->getRelationNumberKeeper());
        $litter->setUbn($location->getUbn());
        $litter->setIsHidden(false);
        $litter->setIsOverwrittenVersion(false);
        $litter->setMessageId(MessageBuilderBase::getNewRequestId());
        $litter->setAnimalMother($mother);
        $litter->setAnimalFather($father);
        $litter->setBornAliveCount($litterSize-$stillbornCount);
        $litter->setStillbornCount($stillbornCount);

        $breedCodeChild = BreedCodeUtil::calculateBreedCodeFromParents($father, $mother, null, true);

        $children = [];
        $newWeights = [];
        $newTailLengths = [];

        /** @var array $child */
        foreach ($childrenContent as $child) {

            $tagToReserve = null;
            $childAnimalToCreate = null;
            $declareBirthRequest = null;

            //                        key      array   null replacement
            $gender = ArrayUtil::get('gender', $child, null);
            $birthProgress = StringUtil::convertEmptyStringToNull(
                ArrayUtil::get('birth_progress', $child, null)
            );

            $hasLambar = $this->hasLambar($child);

            $tailLengthValue = ArrayUtil::get('tail_length', $child, $tailLengthEmptyValue);
            $birthWeightValue = ArrayUtil::get('birth_weight', $child, $birthWeightEmptyValue);

            $isAlive = ArrayUtil::get('is_alive', $child, null);

            if(!$isAlive) { // Stillborn
                $stillborn = new Stillborn();
                $stillborn->setBirthProgress($birthProgress);
                $stillborn->setGender($gender);
                $stillborn->setLitter($litter);
                $stillborn->setTailLength($tailLengthValue);
                $stillborn->setWeight($birthWeightValue);
                $litter->addStillborn($stillborn);

                $this->entityManager->persist($stillborn);
            } else if($isAlive) {
                //Create I&R Declare Birth request per child
                $declareBirthRequest = new DeclareBirth();

                //Generate new requestId

                if($declareBirthRequest->getRequestId()== null) {
                    $requestId = MessageBuilderBase::getNewRequestId();
                    //Add general data to content
                    $declareBirthRequest->setRequestId($requestId);
                }

                if($declareBirthRequest->getAction() == null) {
                    $declareBirthRequest->setAction(ActionType::V_MUTATE);
                }

                $declareBirthRequest->setLogDate(new \DateTime());
                $declareBirthRequest->setRequestState(RequestStateType::OPEN);

                if($declareBirthRequest->getRecoveryIndicator() == null) {
                    $declareBirthRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
                }

                $relationNumberKeeper = null;

                if($client instanceof Client) {
                    $relationNumberKeeper = $client->getRelationNumberKeeper();
                }

                $declareBirthRequest->setRelationNumberKeeper($relationNumberKeeper);

                if($loggedInUser instanceof Person) {
                    $declareBirthRequest->setActionBy($loggedInUser);
                }

                //Assign tag
                if(key_exists('uln_country_code', $child) && key_exists('uln_number', $child)) {
                    $ulnCountryCode = $child['uln_country_code'];
                    $ulnNumber = $child['uln_number'];
                    $uln = $ulnCountryCode.$ulnNumber;
                    //This tag has already been validated in the beginning of this function
                    $tagToReserve = ArrayUtil::get($uln, $tags);

                    if(!$tagToReserve) {
                        $tagRepository->unassignTags($tags);
                        return Validator::createJsonResponse("Opgegeven vrije oormerk: " .$uln ." voor het lam, is niet gevonden.", $statusCode);
                    }

                    //Assign tag details, reserve tag
                    $declareBirthRequest->setUlnCountryCode($tagToReserve->getUlnCountryCode());
                    $declareBirthRequest->setUlnNumber($tagToReserve->getUlnNumber());
                    $tagToReserve->setTagStatus(TagStateType::RESERVED);
                    $tagRepository->persist($tagToReserve);
                }

                $surrogate = null;
                //All surrogates have been validate before processing any births
                if(array_key_exists('surrogate_mother', $child)) {
                    $ulnSurrogate = AnimalArrayReader::getUlnFromArray($child['surrogate_mother']);
                    /** @var Animal $surrogate */
                    if($ulnSurrogate != null) {
                        $surrogate = ArrayUtil::get($ulnSurrogate, $surrogateMothersByUln);
                    }
                }

                //Create child animal
                switch ($gender) {
                    case GenderType::MALE:
                        /** @var Ram $child */
                        $child = new Ram();
                        break;
                    case GenderType::FEMALE:
                        /** @var Ewe $child */
                        $child = new Ewe();
                        break;
                    case GenderType::NEUTER:
                        /** @var Neuter $child */
                        $child = new Neuter();
                        break;
                }

                //Set child details
                $child->setLocation($location);
                $child->setDateOfBirth($dateOfBirth);
                $child->setBirthProgress($birthProgress);
                $child->setIsAlive(true);
                $child->setUlnCountryCode($tagToReserve->getUlnCountryCode());
                $child->setUlnNumber($tagToReserve->getUlnNumber());
                $child->setAnimalOrderNumber($tagToReserve->getAnimalOrderNumber());
                $child->setLocation($location);
                $child->setLocationOfBirth($location);
                $child->setUbnOfBirth($location->getUbn());
                $child->setLambar($hasLambar);
                $child->setLitter($litter);

                if(is_string($breedCodeChild)) {
                    $child->setBreedCode($breedCodeChild);
                }

                //Create new residence
                $animalResidence = new AnimalResidence($tagToReserve->getUlnCountryCode(), false);
                $animalResidence->setAnimal($child);
                $animalResidence->setLocation($location);
                $animalResidence->setStartDate($dateOfBirth);
                $child->addAnimalResidenceHistory($animalResidence);

                $litter->addChild($child);

                $declareBirthRequest->setDateOfBirth($dateOfBirth);
                $declareBirthRequest->setAnimal($child);
                $declareBirthRequest->setGender($gender);
                $declareBirthRequest->setLocation($location);
                $declareBirthRequest->setIsAborted($isAborted);
                $declareBirthRequest->setIsPseudoPregnancy($isPseudoPregnancy);
                $declareBirthRequest->setHasLambar($hasLambar);
                $declareBirthRequest->setLitter($litter);
                $declareBirthRequest->setLitterSize($litterSize);
                $declareBirthRequest->setBirthWeight($birthWeightValue);
                $declareBirthRequest->setBirthTailLength($tailLengthValue);

                if($father) {
                    $declareBirthRequest->setUlnFather($father->getUlnNumber());
                    $declareBirthRequest->setUlnCountryCodeFather($father->getUlnCountryCode());
                    //$father->setLitter($litter);
                    $child->setParentFather($father);
//                    $father->getChildren()->add($child);
                }

                if($mother) {
                    $declareBirthRequest->setUlnMother($mother->getUlnNumber());
                    $declareBirthRequest->setUlnCountryCodeMother($mother->getUlnCountryCode());
                    //$mother->setLitter($litter);
                    $child->setParentMother($mother);
//                    $mother->getChildren()->add($child);
                }

                if($surrogate) {
                    $declareBirthRequest->setUlnSurrogate($surrogate->getUlnNumber());
                    $declareBirthRequest->setUlnCountryCodeSurrogate(($surrogate->getUlnCountryCode()));
                    //$surrogate->setLitter($litter);
                    $child->setSurrogate($surrogate);

//                    $surrogate->getChildren()->add($child);
                }

                // Weight
                if($birthWeightValue != $birthWeightEmptyValue) {
                    $weight = new Weight();
                    $weight->setMeasurementDate($dateOfBirth);
                    $weight->setAnimal($child);
                    $weight->setIsBirthWeight(true);
                    $weight->setWeight($birthWeightValue);
                    $child->addWeightMeasurement($weight);
                    $this->entityManager->persist($weight);
                    $newWeights[] = $weight;
                }

                // Tail Length
                if($tailLengthValue != $tailLengthEmptyValue) {
                    $tailLength = new TailLength();
                    $tailLength->setMeasurementDate($dateOfBirth);
                    $tailLength->setAnimal($child);
                    $tailLength->setLength($tailLengthValue);
                    $child->addTailLengthMeasurement($tailLength);
                    $this->entityManager->persist($tailLength);
                    $newTailLengths[] = $tailLength;
                }

                $location->getAnimals()->add($child);

                $this->entityManager->persist($child);
                $this->entityManager->persist($location);
                $this->entityManager->persist($litter);
                
                if($father){
                    $this->entityManager->persist($father);
                }
                
                if($mother) {
                    $this->entityManager->persist($mother);
                }
                
                if($surrogate) {
                    $this->entityManager->persist($surrogate);
                }

                $declareBirthRequests[] = $declareBirthRequest;
                $litter->addDeclareBirth($declareBirthRequest);

                $this->entityManager->persist($declareBirthRequest);
                $children[] = $child;
            }
        }

        // Persist Litter
        $this->entityManager->persist($litter);

        try {
            $this->entityManager->flush();

            //Update recombination and heterosis values in new litters
            foreach ([$father, $mother] as $parent) {
                if($parent instanceof Animal) {
                    GeneDiversityUpdater::updateByParentId($this->conn, $parent->getId(), false);
                }
            }


            //The animalIdAndDate values can only be generated after the Animal has been persisted and has a primary key
            foreach ([$newWeights, $newTailLengths] as $newMeasurements) {
                /** @var Weight|TailLength $newMeasurement */
                foreach ($newMeasurements as $newMeasurement) {
                    $this->getManager()->refresh($newMeasurement);
                    $newMeasurement->setAnimalIdAndDateByAnimalAndDateTime(
                        $newMeasurement->getAnimal(),
                        $newMeasurement->getMeasurementDate()
                    );
                    $this->getManager()->persist($newMeasurement);
                }
            }

            if (count($newWeights) + count($newTailLengths) > 0) {
                $this->getManager()->flush();
            }

            if (count($children) === 0) {
                // Immediately update production values if all births are stillborn
                ProductionCacher::updateProductionValuesByLitter($this->conn, $litter);
            }

            $children = $this->pedigreeDataGenerator->generate($children, $location);

        } catch (UniqueConstraintViolationException $exception) {
            //Reset tags to UNASSIGNED
            $areAllTagsReset = $tagRepository->unassignTags($tags);
            $tagMessage = $areAllTagsReset ? 'All tagStatusses are reverted to UNASSIGNED' : 'Some tags still have the RESERVED tagStatus';
            $exceptionMessage = 'Create Birth IRSerializer: UniqueConstraintViolationException, message: '.$exception->getMessage();

            return Validator::createJsonResponse($tagMessage.' | '.$exceptionMessage, $statusCode);
        }
        
        return $declareBirthRequests;
    }


    /**
     * @param array $childArray
     * @return bool
     */
    private function hasLambar(array $childArray)
    {
        if (key_exists('has_lambar', $childArray)) {
           $hasLambar = ArrayUtil::get('has_lambar', $childArray, false);

           if (is_bool($hasLambar)) {
               return $hasLambar;
           }

           if (is_string($hasLambar)) {
               return strtolower($hasLambar) === 'true';
           }

           return false;
        }

        $nurtureType = ArrayUtil::get('nurture_type', $childArray,  null);
        return $nurtureType === 'LAMBAR';
    }

    /**
     * @inheritdoc
     */
    function parseDeclareDepart(ArrayCollection $declareDepartContentArray, Client $client, Location $location, $isEditMessage)
    {
        $declareDepartContentArray["type"] = RequestType::DECLARE_DEPART_ENTITY;
        $isExportAnimal = $declareDepartContentArray['is_export_animal'];

        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareDepartContentArray);
        $retrievedAnimal->setIsExportAnimal($isExportAnimal);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareDepartRequest = new DeclareDepart();
        $declareDepartRequest->setAnimal($retrievedAnimal);
        $declareDepartRequest->setDepartDate(new \DateTime($declareDepartContentArray['depart_date']));
        $declareDepartRequest->setReasonOfDepart($declareDepartContentArray['reason_of_depart']);
        $declareDepartRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));
        $declareDepartRequest->setUbnNewOwner($declareDepartContentArray['ubn_new_owner']);

        if($isEditMessage) {
            $requestState = $declareDepartContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareDepartRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareDepartRequest->getResponses());
                if($lastResponse != null) {
                    $declareDepartRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareDepartRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareDepartRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareTagsTransfer(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage)
    {
        $contentArray["type"] = RequestType::DECLARE_TAGS_TRANSFER_ENTITY;

        $ubnNewOwner = $contentArray['ubn_new_owner'];
        $relationNumberAcceptant = $contentArray['relation_number_acceptant'];

        $declareTagsTransfer = new DeclareTagsTransfer();
        $declareTagsTransfer->setRelationNumberAcceptant($relationNumberAcceptant);
        $declareTagsTransfer->setUbnNewOwner($ubnNewOwner);
        $fetchedTag = null;
        $tagsRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);
        $tagsContentArray = $contentArray->get('tags');

        foreach($tagsContentArray as $tag) {
            //create filter to search tag
            $tagFilter = array("ulnCountryCode" => $tag[Constant::ULN_COUNTRY_CODE_NAMESPACE],
                "ulnNumber" => $tag[Constant::ULN_NUMBER_NAMESPACE]);

            //Fetch tag from database
            $fetchedTag = $tagsRepository->findOneBy($tagFilter);

            //If tag was found, add it to the declare transfer request
            if($fetchedTag != null) {

                //Check if Tag status is UNASSIGNED && No animal is assigned to it
                if($fetchedTag->getTagStatus() == TagStateType::UNASSIGNED && $fetchedTag->getAnimal() == null) {

                    //add tag to result set
                    $declareTagsTransfer->addTag($fetchedTag);
                }
            }
        }

        //TODO: NO EDIT YET, Phase 2+

        return $declareTagsTransfer;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareTagReplace(ArrayCollection $contentArray, Client $client, Location $location, $isEditMessage)
    {
        $contentArray["type"] = RequestType::DECLARE_TAG_REPLACE_ENTITY;

        $replaceDate = Utils::getNullCheckedArrayCollectionDateValue('replace_date', $contentArray);
        //Set replaceDate = logDate in MessageBuilder if 'replace_date' was not given.

        //denormalize the content to an object
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($contentArray);
        $declareTagReplace = new DeclareTagReplace();

        $tag = $contentArray['tag'];

        $declareTagReplace->setReplaceDate(new \DateTime($tag['replaced_at']));
        $declareTagReplace->setUlnCountryCodeToReplace($retrievedAnimal->getUlnCountryCode());
        $declareTagReplace->setUlnNumberToReplace($retrievedAnimal->getUlnNumber());
        $declareTagReplace->setAnimalOrderNumberToReplace($retrievedAnimal->getAnimalOrderNumber());
        $declareTagReplace->setAnimalType(AnimalType::sheep);
        $declareTagReplace->setAnimal(null);

        $fetchedTag = null;
        $tagsRepository = $this->entityManager->getRepository(Constant::TAG_REPOSITORY);

        //create filter to search tag
        $tagFilter = array("ulnCountryCode" =>  $ulnCountryCodeReplacement = $tag['uln_country_code'],
          "ulnNumber" => $ulnNumberReplacement= $tag['uln_number']);

        //Fetch tag from database
        $fetchedTag = $tagsRepository->findOneBy($tagFilter);

        //If tag was found, add it to the declare tag replace request
        if($fetchedTag != null) {

            //Check if Tag status is UNASSIGNED && No animal is assigned to it
            if($fetchedTag->getTagStatus() == TagStateType::UNASSIGNED && $fetchedTag->getAnimal() == null) {

                //add tag to result set
                $declareTagReplace->setUlnCountryCodeReplacement($fetchedTag->getUlnCountryCode());
                $declareTagReplace->setUlnNumberReplacement($fetchedTag->getUlnNumber());
                $declareTagReplace->setAnimalOrderNumberReplacement($fetchedTag->getAnimalOrderNumber());
                $fetchedTag->setTagStatus(TagStateType::REPLACING);
                $this->entityManager->persist($fetchedTag);
                $this->entityManager->flush();
            }
        }

        //TODO: NO EDIT YET, Phase 2+

        return $declareTagReplace;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareLoss(ArrayCollection $declareLossContentArray, Client $client, Location $location, $isEditMessage)
    {
        $declareLossContentArray["type"] = RequestType::DECLARE_LOSS_ENTITY;

        //Retrieve animal entity
        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareLossContentArray);

        //Add retrieved animal properties including type to initial animalContentArray
        $declareLossContentArray['animal'] = $retrievedAnimal;

        $dateOfDeath = Utils::getNullCheckedArrayCollectionDateValue(JsonInputConstant::DATE_OF_DEATH, $declareLossContentArray);
        $reasonOfLoss = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::REASON_OF_LOSS, $declareLossContentArray);
        $ubnProcessor = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::UBN_PROCESSOR, $declareLossContentArray);

        $declareLossRequest = new DeclareLoss();
        //Add retrieved animal to DeclareLoss
        $declareLossRequest->setAnimal($retrievedAnimal);
        $declareLossRequest->setDateOfDeath($dateOfDeath);
        $declareLossRequest->setUbnDestructor($ubnProcessor);
        $declareLossRequest->setReasonOfLoss($reasonOfLoss);
        $declareLossRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

        if($isEditMessage) {
            $requestState = $declareLossContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareLossRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareLossRequest->getResponses());
                if($lastResponse != null) {
                    $declareLossRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareLossRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareLossRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareExport(ArrayCollection $declareExportContentArray, Client $client, Location $location, $isEditMessage)
    {
        $declareExportContentArray["type"] = RequestType::DECLARE_EXPORT_ENTITY;

        $exportDate = $declareExportContentArray['depart_date'];
        $isExportAnimal = $declareExportContentArray['is_export_animal'];

        $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareExportContentArray);
        $retrievedAnimal->setIsExportAnimal($isExportAnimal);

        $declareExportRequest = new DeclareExport();
        $declareExportRequest->setAnimal($retrievedAnimal);
        $declareExportRequest->setExportDate(new \DateTime($exportDate));
        $declareExportRequest->setIsExportAnimal($isExportAnimal);
        $declareExportRequest->setReasonOfExport($declareExportContentArray['reason_of_depart']);
        $declareExportRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));

        if($isEditMessage) {
            $requestState = $declareExportContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareExportRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareExportRequest->getResponses());
                if($lastResponse != null) {
                    $declareExportRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareExportRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }
        }

        return $declareExportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseDeclareImport(ArrayCollection $declareImportContentArray, Client $client, $locationOfDestination, $isEditMessage)
    {
        //TODO Phase 2: Built in explicit check for non-EU/EU countries. Now it is filtered by animalUlnNumberOrigin field being null or not.

        $declareImportContentArray["type"] = RequestType::DECLARE_IMPORT_ENTITY;

        $importDate = $declareImportContentArray['arrival_date'];
        if($declareImportContentArray->containsKey('country_origin')) {
            $animalCountryOrigin = $declareImportContentArray['country_origin'];
        } else {
            $animalCountryOrigin = null;
        }

        if($declareImportContentArray->containsKey('animal_uln_number_origin')) {
            $animalUlnNumberOrigin = $declareImportContentArray['animal_uln_number_origin'];
        } else {
            $animalUlnNumberOrigin = null;
        }

        //TODO explicitly check the countries
        //For EU countries the ulnCountryCode needs to be the AnimalCountyOrigin (=country_origin)
        if($animalUlnNumberOrigin == null) {
            $declareImportContentArray[Constant::ULN_COUNTRY_CODE_NAMESPACE] = $animalCountryOrigin;
        }


        if($isEditMessage) {
            $requestId = $declareImportContentArray['request_id'];
            /** @var DeclareImport $declareImportRequest */
            $declareImportRequest = $this->entityManager->getRepository(Constant::DECLARE_IMPORT_REPOSITORY)->getImportByRequestId($client, $requestId);

            //Update values here
            $declareImportRequest->setImportDate(new \DateTime($declareImportContentArray['arrival_date']));

            if($declareImportContentArray->containsKey('country_origin')) {
                $declareImportRequest->setAnimalCountryOrigin($animalCountryOrigin);
            }

            if($declareImportContentArray->containsKey('animal_uln_number_origin')) {
                $declareImportRequest->setAnimalUlnNumberOrigin($animalUlnNumberOrigin);
            }

            $declareImportRequest->setRequestState(RequestStateType::OPEN);


            $requestState = $declareImportContentArray['request_state'];
            if(Utils::hasSuccessfulLastResponse($requestState)) {
                $declareImportRequest->setRecoveryIndicator(RecoveryIndicatorType::J);
                $lastResponse = Utils::returnLastResponse($declareImportRequest->getResponses());
                if($lastResponse != null) {
                    $declareImportRequest->setMessageNumberToRecover($lastResponse->getMessageNumber());
                }
            } else {
                $declareImportRequest->setRecoveryIndicator(RecoveryIndicatorType::N);
            }


        } else {
            //Retrieve animal entity
            $retrievedAnimal = $this->entityGetter->retrieveAnimal($declareImportContentArray);
            $retrievedAnimal->setIsImportAnimal(true);

            //Add retrieved animal and import date to DeclareImport
            $declareImportRequest = new DeclareImport();
            $declareImportRequest->setAnimal($retrievedAnimal);
            $declareImportRequest->setAnimalCountryOrigin($animalCountryOrigin);
            $declareImportRequest->setImportDate(new \DateTime($importDate));
            $declareImportRequest->setAnimalObjectType(Utils::getClassName($retrievedAnimal));
            $declareImportRequest->setIsImportAnimal(true);

            $contentAnimal = $declareImportContentArray['animal'];

            if($contentAnimal != null) {

                if(array_key_exists(Constant::ULN_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::ULN_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareImportRequest->setUlnCountryCode($contentAnimal[Constant::ULN_COUNTRY_CODE_NAMESPACE]);
                    $declareImportRequest->setUlnNumber($contentAnimal[Constant::ULN_NUMBER_NAMESPACE]);
                }

                if(array_key_exists(Constant::PEDIGREE_NUMBER_NAMESPACE, $contentAnimal) && array_key_exists(Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE, $contentAnimal)) {
                    $declareImportRequest->setPedigreeCountryCode($contentAnimal[Constant::PEDIGREE_COUNTRY_CODE_NAMESPACE]);
                    $declareImportRequest->setPedigreeNumber($contentAnimal[Constant::PEDIGREE_NUMBER_NAMESPACE]);
                }
            }
        }

        //At the moment all imports are from location with unknown health status
        $declareImportRequest->setLocation($locationOfDestination);

        return $declareImportRequest;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveTags(ArrayCollection $contentArray, Client $client,$isEditMessage)
    {
        $retrieveTags = new RetrieveTags();

        //set default values
        $retrieveTags->setTagType(TagType::FREE);
        $retrieveTags->setAnimalType(AnimalType::sheep);
        $retrieveTags->setIsManual(false);

        //No custom filter content given, revert to default values
        if($contentArray->count() == 0) {
            return $retrieveTags;
        }

        //set animalType
        if($contentArray->containsKey(Constant::ANIMAL_TYPE_SNAKE_CASE_NAMESPACE)) {
            $retrieveTags->setAnimalType($contentArray->get(Constant::ANIMAL_TYPE_SNAKE_CASE_NAMESPACE));
        }

        //set tagType
        if($contentArray->containsKey(Constant::TAG_TYPE_SNAKE_CASE_NAMESPACE)) {
            $retrieveTags->setTagType($contentArray->get(Constant::TAG_TYPE_SNAKE_CASE_NAMESPACE));
        }

        //set isManual
        if($contentArray->containsKey(Constant::IS_MANUAL)) {
            $retrieveTags->setIsManual($contentArray->get(Constant::IS_MANUAL));
        }

        return $retrieveTags;
    }

    /**
     * @inheritdoc
     */
    function parseRevokeDeclaration(ArrayCollection $revokeDeclarationContentArray, Client $client, $isEditMessage)
    {
        $revokeDeclarationContentArray["type"] = RequestType::REVOKE_DECLARATION_ENTITY;
        $revokeDeclaration = new RevokeDeclaration();

        if($revokeDeclarationContentArray->containsKey(Constant::MESSAGE_ID_SNAKE_CASE_NAMESPACE)) {
            $revokeDeclaration->setMessageId($revokeDeclarationContentArray[Constant::MESSAGE_ID_SNAKE_CASE_NAMESPACE]);
        }

        return $revokeDeclaration;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveAnimals(ArrayCollection $contentArray, Client $client, $isEditMessage) {
        $retrieveAnimals = new RetrieveAnimals();

        if ($contentArray !== null && $contentArray->get(JsonInputConstant::IS_RVO_LEADING) === true) {
            $retrieveAnimals->setIsRvoLeading(true);
        }

        return $retrieveAnimals;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveAnimalDetails(ArrayCollection $contentArray, Client $client,$isEditMessage) {
        $retrieveAnimalDetails = new RetrieveAnimalDetails();

        if($contentArray->containsKey(Constant::ULN_NUMBER_NAMESPACE) && $contentArray->containsKey(Constant::ULN_COUNTRY_CODE_NAMESPACE)) {
            $ulnNumber = $contentArray->get(Constant::ULN_NUMBER_NAMESPACE);
            $ulnCountryCode = $contentArray->get(Constant::ULN_COUNTRY_CODE_NAMESPACE);

            $retrieveAnimalDetails->setUlnNumber($ulnNumber);
            $retrieveAnimalDetails->setUlnCountryCode($ulnCountryCode);
        } else if($contentArray->containsKey(Constant::ANIMAL_ORDER_NUMBER_NAMESPACE)) {
            $animalOrderNumber = $contentArray->get(Constant::ANIMAL_ORDER_NUMBER_NAMESPACE);
            $retrieveAnimalDetails->setAnimalOrderNumber($animalOrderNumber);
        }

        return $retrieveAnimalDetails;
    }

    /**
     * @inheritdoc
     */
    function parseRetrieveEUCountries(ArrayCollection $contentArray, Client $client, $isEditMessage) {
        // TODO: Implement parseRetrieveEUCountries() method.
        $contentArray["type"] = RequestType::RETRIEVE_COUNTRIES_ENTITY;
    }

    /**
     * @param ArrayCollection $contentArray
     * @param $isEditMessage
     * @return RetrieveUbnDetails
     */
    function parseRetrieveUbnDetails(ArrayCollection $contentArray, Client $client, $isEditMessage) {
        $retrieveUbnDetails = new RetrieveUbnDetails();

        return $retrieveUbnDetails;
    }

}
