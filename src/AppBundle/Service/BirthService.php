<?php

namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\RequestMessageBuilder;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\BirthAPIControllerInterface;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Entity\Message;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Stillborn;
use AppBundle\Entity\Tag;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareBirthResponseOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ExceptionUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Util\WorkerTaskUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class BirthService extends DeclareControllerServiceBase implements BirthAPIControllerInterface
{
    const SHOW_OTHER_CANDIDATE_MOTHERS = false;
    const SHOW_OTHER_SURROGATE_MOTHERS = false;

    const MEDIAN_PREGNANCY_DAYS = 145;
    const MINIMUM_DAYS_BETWEEN_BIRTHS = 167;
    const MATING_DAYS_OFFSET = 12;
    const SURROGATE_MOTHER_OFFSET_DAYS = 7;
    const REVOKE_MAX_MONTH_INTERVAL = 6;

    /** @var EntityGetter */
    private $entityGetter;
    /** @var AwsInternalQueueService */
    private $internalQueueService;
    /** @var Logger */
    private $logger;

    public function __construct(AwsExternalQueueService $externalQueueService, CacheService $cacheService, EntityManagerInterface $manager, IRSerializer $irSerializer, RequestMessageBuilder $requestMessageBuilder, UserService $userService,
                                Logger $logger,
                                AwsInternalQueueService $internalQueueService,
                                EntityGetter $entityGetter)
    {
        parent::__construct($externalQueueService, $cacheService, $manager, $irSerializer, $requestMessageBuilder, $userService);
        $this->entityGetter = $entityGetter;
        $this->logger = $logger;
        $this->internalQueueService = $internalQueueService;
    }


    /**
     * @param Request $request
     * @param $litterId
     * @return JsonResponse
     */
    public function getBirth(Request $request, $litterId)
    {
        $location = $this->getSelectedLocation($request);

        if(!$location) {
            return ResultUtil::errorResult('UBN kan niet gevonden worden', 428);
        }

        /** @var Litter $litter */
        $litter = $this->getManager()->getRepository(Litter::class)->findOneBy(['id' => $litterId, 'ubn' => $location->getUbn()]);

        if($litter instanceof Litter) {
            $result = DeclareBirthResponseOutput::createBirth($litter, $litter->getDeclareBirths());
        } else {
            $result = ResultUtil::errorResult('Geen worp gevonden voor gegeven worpId en ubn', 428);
        }

        if($result instanceof JsonResponse) {
            return $result;
        }

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistoryBirths(Request $request)
    {
        $location = $this->getSelectedLocation($request);

        $sql = "SELECT 
                    declare_nsfo_base.id AS id,
                    declare_nsfo_base.log_date AS log_date,
                    declare_nsfo_base.message_id AS message_id,
                    declare_nsfo_base.request_state AS request_state,
                    litter.litter_date AS date_of_birth,
                    litter.stillborn_count AS stillborn_count,
                    litter.born_alive_count AS born_alive_count,
                    litter.is_abortion AS is_abortion,
                    litter.is_pseudo_pregnancy AS is_pseudo_pregnancy,
                    litter.status AS status,
                    mother.uln_country_code AS mother_uln_country_code,
                    mother.uln_number AS mother_uln_number,
                    father.uln_country_code AS father_uln_country_code,
                    father.uln_number AS father_uln_number
                FROM declare_nsfo_base
                    INNER JOIN litter ON declare_nsfo_base.id = litter.id
                    INNER JOIN animal AS mother ON litter.animal_mother_id = mother.id
                LEFT JOIN animal AS father ON litter.animal_father_id = father.id
                WHERE (request_state <> '".RequestStateType::IMPORTED."' OR request_state <> '".RequestStateType::FAILED."') AND declare_nsfo_base.ubn = '" . $location->getUbn() ."'";
        $birthDeclarations = $this->getConnection()->query($sql)->fetchAll();

        $result = DeclareBirthResponseOutput::createHistoryResponse($birthDeclarations);
        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createBirth(Request $request)
    {
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $requestMessages = $this->requestMessageBuilder
            ->build(RequestType::DECLARE_BIRTH_ENTITY,
                $content,
                $client,
                $loggedInUser,
                $location,
                false);

        $result = [];

        //An exception has occured, return response message
        if($requestMessages instanceof JsonResponse) {
            return $requestMessages;
        }

        $logs = ActionLogWriter::createBirth($this->getManager(), $requestMessages, $client);

        //Creating request succeeded, send to Queue

        $litter = null;
        /** @var DeclareBirth $requestMessage */
        foreach ($requestMessages as $requestMessage) {
            //First persist requestmessage, before sending it to the queue
            $this->persist($requestMessage);

            //Send it to the queue and persist/update any changed state to the database
            $result[] = $this->sendMessageObjectToQueue($requestMessage);


            if ($litter === null &&
                ($requestMessage instanceof DeclareBirth || $requestMessage instanceof Stillborn)) {
                if ($requestMessage->getLitter()) {
                    //All these births belong to the same litter
                    $litter = $requestMessage->getLitter();
                }
            }
        }

        $this->saveNewestDeclareVersion($content, $litter);

        //Send workerTask to update resultTable records of parents and children
        $this->sendTaskToQueue($this->internalQueueService, WorkerTaskUtil::createResultTableMessageBodyByBirthRequests($requestMessages));

        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($location);

        ActionLogWriter::completeActionLog($this->getManager(), $logs);

        return new JsonResponse($result, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resendCreateBirth(Request $request)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::DEVELOPER);
        if(!$isAdmin) { return AdminValidator::getStandardErrorResponse(); }

        $requestMessages = $this->getManager()->getRepository(DeclareBirth::class)->findBy(['requestState' => RequestStateType::OPEN]);

        $openCount = count($requestMessages);
        $resentCount = 0;

        if ($openCount > 0) {
            $logs = ActionLogWriter::createBirth($this->getManager(), $requestMessages);

            //Creating request succeeded, send to Queue
            /** @var DeclareBirth $requestMessage */
            foreach ($requestMessages as $requestMessage) {

                if($requestMessage->getResponses()->count() === 0) {
                    //Resend it to the queue and persist/update any changed state to the database
                    $result[] = $this->sendMessageObjectToQueue($requestMessage);
                    $resentCount++;
                }
            }

            ActionLogWriter::completeActionLog($this->getManager(), $logs);
        }

        return new JsonResponse(['DeclareBirth' => ['found open declares' => $openCount, 'open declares resent' => $resentCount]], 200);
    }



    public function revokeBirth(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $content = RequestUtil::getContentAsArray($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $statusCode = 428;
        $litterId = null;

        if (!key_exists('litter_id', $content->toArray())) {
            return new JsonResponse(
                array (
                    Constant::RESULT_NAMESPACE => array (
                        'code' => $statusCode,
                        "message" => "Mandatory Litter Id not given.",
                    )
                ), $statusCode);
        }

        $litterId = $content['litter_id'];
        /** @var Litter $litter */
        $litter = $this->getManager()->getRepository(Litter::class)->findOneBy(array ('id' => $litterId));

        if (!$litter) {
            return new JsonResponse(
                array (
                    Constant::RESULT_NAMESPACE => array (
                        'code' => $statusCode,
                        "message" => "No litter was not found.",
                    )
                ), $statusCode);
        }

        $litterClone = clone $litter;
        $childrenToRemove = [];
        $stillbornsToRemove = [];

        //Check if birth registration is within a time span of maxMonthInterval from now,
        //then, and only then, the revoke and thus deletion of child animal is allowed
        foreach ($litter->getChildren() as $child) {
            $dateInterval = $child->getDateOfBirth()->diff(new \DateTime());

            if($dateInterval->y > 0 || $dateInterval->m > self::REVOKE_MAX_MONTH_INTERVAL) {
                return new JsonResponse(
                    array (
                        Constant::RESULT_NAMESPACE => array (
                            'code' => $statusCode,
                            "message" => $child->getUlnCountryCode() .$child->getUlnNumber() . " heeft een geregistreerde geboortedatum dat langer dan "
                                .self::REVOKE_MAX_MONTH_INTERVAL ." maand geleden is, zodoende is het niet geoorloofd om de melding in te trekken en daarmee de geboorte van het dier ongedaan te maken.",
                        )
                    ), $statusCode);
            }
        }

        //Remove still born childs
        foreach ($litter->getStillborns() as $stillborn) {
            $this->getManager()->remove($stillborn);
            $stillbornsToRemove[] = $stillborn;
        }

        //Send workerTask to update productionValues of parents
        $this->sendTaskToQueue($this->internalQueueService, WorkerTaskUtil::createResultTableMessageBodyForBirthRevoke($litter));

        //Remove alive child animal
        try {
            /** @var Animal $child */
            foreach ($litter->getChildren() as $child) {

                $childrenToRemove[] = $child;

                //Remove animal residence
                $residenceHistory = $child->getAnimalResidenceHistory();
                foreach ($residenceHistory as $residence) {
                    $this->getManager()->remove($residence);
                }

                //Remove weights
                $weights = $child->getWeightMeasurements();
                foreach ($weights as $weight) {
                    $this->getManager()->remove($weight);
                }

                //Remove tail lengths
                $tailLengths = $child->getTailLengthMeasurements();
                foreach ($tailLengths as $tailLength) {
                    $this->getManager()->remove($tailLength);
                }

                //Remove bodyfats
                $bodyFats = $child->getBodyFatMeasurements();
                foreach ($bodyFats as $bodyFat) {
                    $this->getManager()->remove($bodyFat);
                }

                //Remove exteriors
                $exteriors = $child->getExteriorMeasurements();
                foreach ($exteriors as $exterior) {
                    $this->getManager()->remove($exterior);
                }

                //Remove muscleThickness
                $muscleThicknesses = $child->getMuscleThicknessMeasurements();
                foreach ($muscleThicknesses as $muscleThickness) {
                    $this->getManager()->remove($muscleThickness);
                }

                //Remove gender change history items
                $genderHistories = $child->getGenderHistory();
                foreach ($genderHistories as $genderHistory) {
                    $this->getManager()->remove($genderHistory);
                }


                //Remove REVOKED declare losses, exports and departs
                foreach ([$child->getDeaths(), $child->getDepartures(), $child->getExports(), $child->getArrivals()] as $declaresToRemove) {
                    /** @var DeclareLoss|DeclareDepart|DeclareExport $declareToRemove */
                    foreach($declaresToRemove as $declareToRemove) {
                        if($declareToRemove->getRequestState() === RequestStateType::REVOKED || $declareToRemove->getRequestState() === RequestStateType::FAILED) {

                            foreach ($declareToRemove->getResponses() as $response) {
                                $this->getManager()->remove($response);
                            }

                            if($declareToRemove instanceof DeclareDepart || $declaresToRemove instanceof DeclareArrival) {
                                $message = $this->getManager()->getRepository(Message::class)->findOneBy(['requestMessage'=>$declareToRemove]);
                                $this->getManager()->remove($message);
                            }

                            $this->getManager()->remove($declareToRemove);

                        } else {

                            $declareType = 'melding';
                            if($declareToRemove instanceof DeclareLoss) {
                                $declareType = 'sterftemelding';
                            } elseif($declareToRemove instanceof DeclareArrival) {
                                $declareType = 'aanvoermelding';
                            } elseif($declareToRemove instanceof DeclareDepart) {
                                $declareType = 'afvoermelding';
                            } elseif($declareToRemove instanceof DeclareExport) {
                                $declareType = 'exportmelding';
                            }

                            return Validator::createJsonResponse('Er bestaat nog een '.$declareType.' die niet is ingetrokken voor dit dier op ubn: '.$declareToRemove->getUbn(), $statusCode);
                        }
                    }
                }


                if($child->getLatestBreedGrades()) {
                    $this->getManager()->remove($child->getLatestBreedGrades());
                }


                $breedValueRepository = $this->getManager()->getRepository(BreedValue::class);
                $breedValues = $breedValueRepository->findBy(['animal'=>$child]);
                foreach ($breedValues as $breedValue) {
                    $this->getManager()->remove($breedValue);
                }


                //Flush the removes separately
                $this->getManager()->flush();

                //Restore tag if it does not exist
                $tagToRestore = $this->getManager()->getRepository(Tag::class)->findByUlnNumberAndCountryCode($child->getUlnCountryCode(), $child->getUlnNumber());

                if ($tagToRestore) {
                    $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
                    $this->getManager()->persist($tagToRestore);
                    $this->getManager()->flush();
                } else {
                    $tagToRestore = $this->getManager()->getRepository(Tag::class)->restoreTagWithPrimaryKeyCheck($this->getManager(), $location, $client, $child->getUlnCountryCode(), $child->getUlnNumber());
                    if($tagToRestore instanceof JsonResponse) { return $tagToRestore; }
                }

                //Remove child from location
                if ($location->getAnimals()->contains($child)) {
                    $location->getAnimals()->removeElement($child);
                    $this->getManager()->persist($location);
                }

                $litter->removeChild($child);
                $this->getManager()->persist($litter);
                $this->getManager()->flush();

                $child->setParentFather(null);
                $child->setParentMother(null);
                $child->setSurrogate(null);

                $this->getManager()->persist($child);
                $this->getManager()->flush();

                $declareBirths = $litter->getDeclareBirths();

                foreach ($declareBirths as $declareBirth) {
                    if ($declareBirth->getAnimal() != null) {
                        if ($declareBirth->getAnimal()->getUlnNumber() == $child->getUlnNumber()) {
                            $declareBirthResponses = $declareBirth->getResponses();
                            $declareBirth->setRequestState(RequestStateType::REVOKED);

                            /** @var DeclareBirthResponse $declareBirthResponse */
                            foreach ($declareBirthResponses as $declareBirthResponse) {
                                if($declareBirthResponse->getAnimal() != null) {
                                    if ($declareBirthResponse->getAnimal()->getUlnNumber() == $child->getUlnNumber()) {
                                        $declareBirthResponse->setAnimal(null);
                                        $this->getManager()->persist($declareBirthResponse);

                                    }
                                }
                            }
                            //Remove child animal
                            $declareBirth->setAnimal(null);
                            $this->getManager()->persist($declareBirth);
                        }
                    }
                }

                //Remove child animal
                $this->getManager()->remove($child);
            }

            $this->getManager()->flush();

        } catch (ForeignKeyConstraintViolationException $e) {
            $exceptionMessage = $e->getMessage();
            $this->logger->critical($exceptionMessage);

            $errorMessage = "Voor de kinderen in deze worp zijn nieuwe gegevens geregistreerd, waardoor het niet mogelijk is om deze dieren via een geboortemeldingintrekking te verwijderen.";

            $blockedTable = ExceptionUtil::getBlockedTableInForeignKeyConstraintViolationException($e);
            $referenceTable = ExceptionUtil::getReferenceTableInForeignKeyConstraintViolationException($e);
            if($blockedTable) {
                $errorMessage = $errorMessage.' De geblokkeerde tabel = '.$blockedTable.'.';
            }
            if($referenceTable) {
                $errorMessage = $errorMessage.' De referentie tabel = '.$referenceTable.'.';
            }

            return ResultUtil::errorResult($errorMessage, $statusCode);
        }


        //Send workerTask to update productionValues of parents
        $this->sendTaskToQueue($this->internalQueueService, WorkerTaskUtil::createResultTableMessageBodyForBirthRevoke($litterClone));

        //Clear cache for this location, to reflect changes on the livestock.
        $this->clearLivestockCacheForLocation($location);

        //Re-retrieve litter, check count
        /** @var Litter $litter */
        $litter = $this->getManager()->getRepository(Litter::class)->findOneBy(array ('id'=> $litterId));

        $succeeded = true;

        foreach ($childrenToRemove as $child) {
            if($litter->getChildren()->contains($child)) {
                $succeeded = false;
                break;
            }
        }

        if($succeeded) {
            foreach ($stillbornsToRemove as $child) {
                if($litter->getStillborns()->contains($child)) {
                    $succeeded = false;
                    break;
                }
            }
        }

        $childrenToRemove = null;
        $stillbornsToRemove = null;

        if($succeeded) {
            $litter->setStatus(RequestStateType::REVOKED);
            $litter->setRequestState(RequestStateType::REVOKED);
            $litter->setRevokeDate(new \DateTime());
            $litter->setRevokedBy($loggedInUser);

            $this->getManager()->persist($litter);
            $this->getManager()->flush();

            $revokeMessages = [];
            $declareBirthCount = 0;
            $declareBirthResponseCount = 0;
            //Create revoke request for every declareBirth request
            if ($litter->getDeclareBirths()->count() > 0) {
                foreach ($litter->getDeclareBirths() as $declareBirth) {
                    $declareBirthCount++;
                    $declareBirthResponse = $this->entityGetter
                        ->getResponseDeclarationByMessageId($declareBirth->getMessageId());

                    if ($declareBirthResponse) {
                        $declareBirthResponseCount++;
                        //Only successful responses contain messageNumbers and can be revoked
                        if($declareBirthResponse->getMessageNumber() != null) {
                            $message = new ArrayCollection();
                            $message->set(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE, $declareBirthResponse->getMessageNumber());
                            $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $message, $client, $loggedInUser, $location);
                            $this->persist($revokeDeclarationObject);
                            $this->persistRevokingRequestState($this->entityGetter, $revokeDeclarationObject->getMessageNumber());
                            $this->sendMessageObjectToQueue($revokeDeclarationObject);
                            $revokeMessages[] = $revokeDeclarationObject;
                        }
                    }
                }
            }

            //Create response
            $statusCode = 200;
            $message = 'OK';

            $missingMessages = $declareBirthCount-$declareBirthResponseCount;
            if ($declareBirthCount > $declareBirthResponseCount) {
                $message = 'There are '.$declareBirthCount.' declareBirths found for the litter, which are missing '.$missingMessages.' responses';
                $statusCode = 428;
            } elseif($declareBirthCount == 0 && $litter->getBornAliveCount() != 0) {
                $message = 'The litter does not contain any declareBirths';
                $statusCode = 428;
            }

            ActionLogWriter::revokeLitter($this->getManager(), $litter, $loggedInUser, $client);

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => [
                'code' => $statusCode,
                'revokes' => $revokeMessages,
                'message' => $message,
            ]), $statusCode);
        }

        return ResultUtil::errorResult("Failed to revoke and remove all child and stillborn animals", $statusCode);
    }


    /**
     * @param Request $request
     * @param string $uln
     * @return JsonResponse
     */
    public function getCandidateFathers(Request $request, $uln)
    {
        $content = RequestUtil::getContentAsArray($request);
        $dateOfBirth = new \DateTime();

        if(key_exists('date_of_birth', $content->toArray())) {
            $dateOfBirth = new \DateTime($content["date_of_birth"]);
        }

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        /** @var Ewe $mother */
        $mother = null;
        $motherUlnCountryCode = null;
        $motherUlnNumber = null;

        if($uln) {
            $motherUlnCountryCode = mb_substr($uln, 0, 2);
            $motherUlnNumber = substr($uln, 2);
            $mother = $this->getManager()->getRepository(Animal::class)->findOneBy(array('ulnCountryCode'=>$motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
        }

        if(!$mother) {
            $statusCode = 428;
            return new JsonResponse(
                array(
                    Constant::RESULT_NAMESPACE => array(
                        'code'=> $statusCode,
                        'message'=> "Moeder met opgegeven ULN: " .$motherUlnCountryCode . $motherUlnNumber ." is niet gevonden."
                    )
                ), $statusCode
            );
        }

        $result = [];
        $candidateFathers = $this->getManager()->getRepository(DeclareBirth::class)->getCandidateFathers($mother, $dateOfBirth);
        $otherCandidateFathers = $this->getManager()->getRepository(Animal::class)->getLiveStock($location, $this->getCacheService(), true, Ram::class);
        $filteredOtherCandidateFathers = [];
        $suggestedCandidateFathers = [];
        $suggestedCandidateFatherIds = [];

        /** @var Animal $animal */
        foreach ($candidateFathers as $animal) {
            $suggestedCandidateFatherIds['id'] = $animal->getId();
            $suggestedCandidateFathers[] = [
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

        /** @var Animal $animal */
        foreach ($otherCandidateFathers as $animal) {
            if(!array_key_exists($animal->getId(), $suggestedCandidateFatherIds)) {
                $filteredOtherCandidateFathers[] = [
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
        }

        $filteredOtherCandidateFathersIds = null;

        $result['suggested_candidate_fathers'] = $suggestedCandidateFathers;
        $result['other_candidate_fathers'] = $filteredOtherCandidateFathers;

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @param string $uln
     * @return JsonResponse
     */
    public function getCandidateSurrogateMothers(Request $request, $uln)
    {
        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        /** @var Ewe $mother */
        $mother = null;
        $motherUlnCountryCode = null;
        $motherUlnNumber = null;

        if($uln) {
            $motherUlnCountryCode = mb_substr($uln, 0, 2);
            $motherUlnNumber = substr($uln, 2);
            $mother = $this->getManager()->getRepository(Animal::class)->findOneBy(array ('ulnCountryCode' => $motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
        }

        if(!$mother) {
            $statusCode = 428;
            return new JsonResponse(
                array(
                    Constant::RESULT_NAMESPACE => array(
                        'code'=> $statusCode,
                        'message'=> "Moeder met opgegeven ULN: " .$motherUlnCountryCode . $motherUlnNumber ." is niet gevonden."
                    )
                ), $statusCode
            );
        }

        $content = RequestUtil::getContentAsArray($request);
        if($content->containsKey('date_of_birth')) {
            $dateOfBirth = new \DateTime($content->get('date_of_birth'));
        } else {
            $dateOfBirth = new \DateTime();
        }

        $suggestedCandidatesResult = [];
        $otherCandidatesResult = [];
        $result = [];

        $surrogateMotherCandidates = $this->getManager()->getRepository(DeclareBirth::class)->getCandidateSurrogateMothers($location , $mother);

        $offsetDateFromNow = $dateOfBirth->modify('-' . self::SURROGATE_MOTHER_OFFSET_DAYS .'days');

        /** @var Ewe $animal */
        foreach ($surrogateMotherCandidates as $animal) {

            //Check if surrogate mother candidate has given birth to childeren within the last 6 months
            if($animal->getChildren()->count() == 0) {
                if(self::SHOW_OTHER_SURROGATE_MOTHERS) {
                    $otherCandidatesResult[] = [
                        JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
                        JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
                        JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animal->getPedigreeCountryCode(),
                        JsonInputConstant::PEDIGREE_NUMBER => $animal->getPedigreeNumber(),
                        JsonInputConstant::WORK_NUMBER => $animal->getAnimalOrderNumber(),
                        JsonInputConstant::GENDER => $animal->getGender(),
                        JsonInputConstant::DATE_OF_BIRTH => $animal->getDateOfBirth(),
                        JsonInputConstant::DATE_OF_DEATH => $animal->getDateOfDeath(),
                        JsonInputConstant::IS_ALIVE => $animal->getIsAlive(),
                        JsonInputConstant::UBN => $location->getUbn(),
                        JsonInputConstant::IS_HISTORIC_ANIMAL => false,
                        JsonInputConstant::IS_PUBLIC => $animal->isAnimalPublic(),
                    ];
                }
                continue;
            }

            $childeren = $animal->getChildren();
            $addToOtherCandidates = true;

            /** @var Animal $child */
            foreach ($childeren as $child) {
                if($child->getDateOfBirth()) {
                    //Add as a true candidate surrogate to list
                    if(TimeUtil::getDaysBetween($child->getDateOfBirth(), $offsetDateFromNow) > self::MINIMUM_DAYS_BETWEEN_BIRTHS) {
                        $suggestedCandidatesResult[] = [
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
                        $addToOtherCandidates = false;
                        break;
                    }

                }
            }

            if (!$addToOtherCandidates) {
                continue;
            }

            if(self::SHOW_OTHER_SURROGATE_MOTHERS) {
                $otherCandidatesResult[] = [
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
        }


        $result['suggested_candidate_surrogates'] = $suggestedCandidatesResult;
        $result['other_candidate_surrogates'] = $otherCandidatesResult;

        return ResultUtil::successResult($result);
    }


    public function getCandidateMothers(Request $request) {
        $content = RequestUtil::getContentAsArray($request);
        $dateOfBirth = new \DateTime();

        if(key_exists('date_of_birth', $content->toArray())) {
            $dateOfBirth = new \DateTime($content["date_of_birth"]);
        }

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        $suggestedCandidatesResult = [];
        $otherCandidatesResult = [];
        $result = [];

        $motherCandidates = $this->getManager()->getRepository(Animal::class)->getLiveStock($location , $this->getCacheService(), true, Ewe::class);

        $result['suggested_candidate_mothers'] = $suggestedCandidatesResult;
        $result['other_candidate_mothers'] = $otherCandidatesResult;

        //Animal has no registered matings, thus it is not a true candidate
        /** @var Ewe $animal */
        foreach ($motherCandidates as $animal) {
            if($animal->getMatings()->count() == 0 ) {
                if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                    $otherCandidatesResult[] = [
                        JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
                        JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
                        JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animal->getPedigreeCountryCode(),
                        JsonInputConstant::PEDIGREE_NUMBER => $animal->getPedigreeNumber(),
                        JsonInputConstant::WORK_NUMBER => $animal->getAnimalOrderNumber(),
                        JsonInputConstant::GENDER => $animal->getGender(),
                        JsonInputConstant::DATE_OF_BIRTH => $animal->getDateOfBirth(),
                        JsonInputConstant::DATE_OF_DEATH => $animal->getDateOfDeath(),
                        JsonInputConstant::IS_ALIVE => $animal->getIsAlive(),
                        JsonInputConstant::UBN => $location->getUbn(),
                        JsonInputConstant::IS_HISTORIC_ANIMAL => false,
                        JsonInputConstant::IS_PUBLIC => $animal->isAnimalPublic(),
                        JsonInputConstant::BREED_CODE => $animal->getBreedCode(),
                    ];
                }
                continue;
            }

            $addToOtherCandidates = true;
            $checkAnimalForMatings = true;

            $children = $this->getManager()->getRepository(DeclareBirth::class)->getChildrenOfEwe($animal);

            //Check if Mother has children that are born in the last 5,5 months if so, it is not a true candidate
            /** @var Animal $child */
            foreach ($children as $child) {
                if($child->getDateOfBirth()) {
                    $daysbetweenCurrentBirthAndPreviousBirths = TimeUtil::getDaysBetween($child->getDateOfBirth(), $dateOfBirth);

                    if(!($daysbetweenCurrentBirthAndPreviousBirths >= self::MINIMUM_DAYS_BETWEEN_BIRTHS)) {
                        $checkAnimalForMatings = false;
                        break;
                    }
                }
            }

            //animal has given birth within the last 167 days, thus it is not a true candidate
            if(!$checkAnimalForMatings) {
                if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                    $otherCandidatesResult[] = [
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
                        JsonInputConstant::BREED_CODE => $animal->getBreedCode(),
                    ];
                }
                continue;
            }

            $matings = $animal->getMatings();

            /** @var Mate $mating */
            foreach ($matings as $mating) {
                $lowerboundPregnancyDays = self::MEDIAN_PREGNANCY_DAYS - self::MATING_DAYS_OFFSET;
                $upperboundPregnancyDays = self::MEDIAN_PREGNANCY_DAYS + self::MATING_DAYS_OFFSET;

                //Compare if final father suggestion date is before dateOfBirth lower- & upperbound
                $expectedBirthDateLowerbound = clone $mating->getStartDate();
                $expectedBirthDateLowerbound->modify("+" . (string) $lowerboundPregnancyDays . " days");

                $expectedBirthDateUpperbound = clone $mating->getEndDate();
                $expectedBirthDateUpperbound->modify("+" . (string) $upperboundPregnancyDays . " days");

                //Get the date difference between the computed dateOfBirth and the actual given dateOfBirth
                //Check if it is betweeen date interval of given upperBound and lowerBound
                if (TimeUtil::isDateBetweenDates($dateOfBirth, $expectedBirthDateLowerbound, $expectedBirthDateUpperbound)) {
                    $candidateFathers[] = $mating->getStudRam();

                    //Add as a true candidate surrogate to list
                    $suggestedCandidatesResult[] = [
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
                        JsonInputConstant::BREED_CODE => $animal->getBreedCode(),
                    ];
                    $addToOtherCandidates = false;
                    break;
                }
            }

            if (!$addToOtherCandidates) {
                continue;
            }

            if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                $otherCandidatesResult[] = [
                    JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
                    JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
                    JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animal->getPedigreeCountryCode(),
                    JsonInputConstant::PEDIGREE_NUMBER => $animal->getPedigreeNumber(),
                    JsonInputConstant::WORK_NUMBER => $animal->getAnimalOrderNumber(),
                    JsonInputConstant::GENDER => $animal->getGender(),
                    JsonInputConstant::DATE_OF_BIRTH => $animal->getDateOfBirth(),
                    JsonInputConstant::DATE_OF_DEATH => $animal->getDateOfDeath(),
                    JsonInputConstant::IS_ALIVE => $animal->getIsAlive(),
                    JsonInputConstant::UBN => $location->getUbn(),
                    JsonInputConstant::IS_HISTORIC_ANIMAL => false,
                    JsonInputConstant::IS_PUBLIC => $animal->isAnimalPublic(),
                    JsonInputConstant::BREED_CODE => $animal->getBreedCode(),
                ];
            }

        }

        $result['suggested_candidate_mothers'] = $suggestedCandidatesResult;
        $result['other_candidate_mothers'] = $otherCandidatesResult;

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getBirthErrors(Request $request) {
        return ResultUtil::successResult([]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function processInternalQueueMessage(Request $request)
    {
        $messageId = RequestUtil::getContentAsArray($request)->get('message_id');
        $taskType = 'DECLARE_BIRTH';
        $jsonMessage = $request->getContent();

        $declareBirthResponse = WorkerTaskUtil::deserializeMessageToDeclareBirthResponse($request, $this->irSerializer);

        $message = 'Message is not a DeclareBirthResponse';
        $statusCode = 428;
        if($declareBirthResponse instanceof DeclareBirthResponse) {
            $sendToQresult = $this->internalQueueService
                ->sendDeclareResponse($jsonMessage, $taskType, $messageId);

            $statusCode = $sendToQresult['statusCode'];
            $message = $jsonMessage;
        }

        return new JsonResponse($message,$statusCode);
    }


}