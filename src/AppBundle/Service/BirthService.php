<?php

namespace AppBundle\Service;


use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\BirthAPIControllerInterface;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\DeclareArrival;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareWeight;
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
use AppBundle\model\ParentIdsPair;
use AppBundle\Output\DeclareBirthResponseOutput;
use AppBundle\Service\InbreedingCoefficient\InbreedingCoefficientUpdaterService;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\ExceptionUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\WorkerTaskUtil;
use AppBundle\Validation\AdminValidator;
use AppBundle\Worker\DirectProcessor\DeclareProcessorBase;
use AppBundle\Worker\Task\WorkerMessageBodyLitter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\Validator\Constraints\Time;

class BirthService extends DeclareControllerServiceBase implements BirthAPIControllerInterface
{
    const PERSIST_MAX_RETRIES = 10;

    const SHOW_OTHER_CANDIDATE_MOTHERS = false;
    const SHOW_OTHER_SURROGATE_MOTHERS = false;

    const MEDIAN_PREGNANCY_DAYS = 145;
    const MINIMUM_DAYS_BETWEEN_BIRTHS = 167;
    const MATING_DAYS_OFFSET = 12;
    const SURROGATE_MOTHER_OFFSET_DAYS = 7;
    const REVOKE_MAX_MONTH_INTERVAL = 6;
    const SURROGATE_MOTHER_MAX_BIRTH_OFFSET_FROM_NEW_BIRTH = 21;

    const MIN_FATHER_AGE_AT_BIRTH_IN_MONTHS = 8;

    /** @var EntityGetter */
    private $entityGetter;
    /** @var AwsInternalQueueService */
    private $internalQueueService;
    /** @var Logger */
    private $logger;

    /** @var InbreedingCoefficientUpdaterService */
    private $inbreedingCoefficientUpdaterService;

    /**
     * @param InbreedingCoefficientUpdaterService $inbreedingCoefficientUpdaterService
     */
    public function setInbreedingCoefficientUpdaterService(InbreedingCoefficientUpdaterService $inbreedingCoefficientUpdaterService)
    {
        $this->inbreedingCoefficientUpdaterService = $inbreedingCoefficientUpdaterService;
    }

    /**
     * @required load at start up
     *
     * @param EntityGetter $entityGetter
     */
    public function setEntityGetter(EntityGetter $entityGetter)
    {
        $this->entityGetter = $entityGetter;
    }


    /**
     * @required load at start up
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @required load at start up
     *
     * @param AwsInternalQueueService $internalQueueService
     */
    public function setInternalQueueService(AwsInternalQueueService $internalQueueService)
    {
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
            return ResultUtil::errorResult('UBN kan niet gevonden worden', Response::HTTP_PRECONDITION_REQUIRED);
        }

        /** @var Litter $litter */
        $litter = $this->getManager()->getRepository(Litter::class)->findOneBy(['id' => $litterId, 'ubn' => $location->getUbn()]);

        if($litter instanceof Litter) {
            $result = DeclareBirthResponseOutput::createBirth($litter, $litter->getDeclareBirths());
        } else {
            $result = ResultUtil::errorResult('Geen worp gevonden voor gegeven worpId en ubn', Response::HTTP_PRECONDITION_REQUIRED);
        }

        if($result instanceof JsonResponse) {
            return $result;
        }

        return ResultUtil::successResult($result);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws DBALException
     */
    public function getHistoryBirths(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $this->nullCheckLocation($location);

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
                    mother.collar_color AS mother_collar_color,
                    mother.collar_number AS mother_collar_number,
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
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);
        $this->validateRelationNumberKeeperOfLocation($location);

        $clientId = $client->getId();
        $locationId = $location->getId();

        $useRvoLogic = $location->isDutchLocation();

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

        $logs = ActionLogWriter::createBirth($this->getManager(), $requestMessages, $content, $loggedInUser, $client);

        //Creating request succeeded, send to Queue

        $litter = null;

        try {
            /** @var DeclareBirth $requestMessage */
            foreach ($requestMessages as $requestMessage) {
                //First persist requestmessage, before sending it to the queue
                $this->persist($requestMessage);

                if ($litter === null &&
                    ($requestMessage instanceof DeclareBirth || $requestMessage instanceof Stillborn) &&
                    $requestMessage->getLitter()
                ) {
                    //All these births belong to the same litter
                    $litter = $requestMessage->getLitter();
                }
            }
            $this->getManager()->flush();

            $logIds = array_map(function(ActionLog $actionLog) {
                return $actionLog->getId();
            }, $logs);

        } catch (Exception $exception) {
            //Roll back tag and animal changes
            $this->rollBackCreateBirth($requestMessages, $litter, $clientId, $locationId);
            throw $exception;
        }

        // If litter is null it must a finalized litter only containing stillborns
        if (!$litter) {
            ActionLogWriter::completeActionLog($this->getManager(), $logs);
            return new JsonResponse($result, 200);
        }

        // Prepare data in case of Entity Manager reset
        $requestMessagesByPrimaryKeys = $this->getManager()->getRepository(DeclareBirth::class)
            ->refreshBirthsAndAddPrimaryKeysAsArrayKey($requestMessages);
        $birthIds = array_keys($requestMessagesByPrimaryKeys);

        $this->getManager()->refresh($litter);
        $litterId = $litter->getId();

        /** @var DeclareBirth $requestMessage */
        foreach ($birthIds as $primaryKey) {
            $retryCount = 0;
            $successfulFlush = false;
            $uniqueConstraintViolationException = null;

            do {
                try {
                    $requestMessage = $requestMessagesByPrimaryKeys[$primaryKey];
                    $result[] = $this->runDeclareBirthWorkerLogic($requestMessage);
                    $this->getManager()->flush();
                    $successfulFlush = true;
                } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
                    $this->resetManager();
                    // Retrieve all entities
                    $requestMessagesByPrimaryKeys = $this->getManager()->getRepository(DeclareBirth::class)
                        ->findByIds($birthIds);
                    $litter = $this->getManager()->getRepository(Litter::class)->find($litterId);
                    $location = $this->getManager()->getRepository(Location::class)->find($locationId);
                    $logs = $this->getManager()->getRepository(ActionLog::class)->findByIds($logIds);
                }
            } while (!$successfulFlush && +$retryCount <= self::PERSIST_MAX_RETRIES);

            if (!$successfulFlush) {
                $this->rollBackCreateBirth($requestMessagesByPrimaryKeys, $litter, $clientId, $locationId);
                throw $uniqueConstraintViolationException ? $uniqueConstraintViolationException :
                    new Exception($this->translateUcFirstLower(
                        $this->translateUcFirstLower('SOMETHING WENT WRONG')
                    ));
            }
        }

        $this->saveNewestDeclareVersion($content, $litter);

        $this->updateLitterStatus($litter, $useRvoLogic);
        $this->updateResultTableValuesByBirthRequests($requestMessagesByPrimaryKeys, $useRvoLogic);

        $this->generateInbreedingCoefficients($litter);

        if (!$useRvoLogic) {
            $this->directlyUpdateResultTableValuesByAnimalIds($litter->getAllAnimalIds());
        }

        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($location);

        ActionLogWriter::completeActionLog($this->getManager(), $logs);

        return new JsonResponse($result, 200);
    }


    /**
     * @param DeclareBirth[] $requestMessages
     * @param Litter $litter
     * @param int $clientId
     * @param int $locationId
     */
    private function rollBackCreateBirth($requestMessages, ?Litter $litter, $clientId, $locationId)
    {
        $animalIds = [];
        $litterId = $litter ? $litter->getId() : null;
        $reservedTagUlns = [];
        foreach ($requestMessages as $requestMessage) {
            if ($requestMessage->getAnimal() && $requestMessage->getAnimal()->getId()) {
                $animalIds[] = $requestMessage->getAnimal()->getId();
            }
            if (!empty($requestMessage->getUln())) {
                $reservedTagUlns[] = $requestMessage->getUln();
            }
        }

        $this->rollBackTags($reservedTagUlns, $clientId, $locationId);
        $this->removeAnimals($animalIds);
        $this->removeLitter($litterId);
    }


    /**
     * @param int $litterId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     * @throws Exception
     */
    public function resendCreateBirthsByLitterId($litterId)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::SUPER_ADMIN);
        if(!$isAdmin) { return AdminValidator::getStandardErrorResponse(); }

        $requestMessages = $this->getManager()->getRepository(DeclareBirth::class)->findBy(
            [
                'requestState' => RequestStateType::OPEN,
                'isRvoMessage' => true,
                'litter' => $litterId
            ]
        );

        if (empty($requestMessages)) {
            return ResultUtil::errorResult(
                $this->translator->trans("NO OPEN DECLARE BIRTHS FOUND. RESENDING IMPOSSIBLE."),
                Response::HTTP_NOT_FOUND
            );
        }

        $logs = ActionLogWriter::createBirth($this->getManager(), $requestMessages, new ArrayCollection(), $loggedInUser);

        $openCount = count($requestMessages);
        $resentCount = 0;

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

        if ($openCount > $resentCount) {
            $errorMessage = $this->translator->trans('ONLY %openDeclaresResent% OF THE %foundOpenDeclares% WHERE RESENT',
                    [
                        '%openDeclaresResent%' => $resentCount,
                        '%foundOpenDeclares%' => $openCount,
                    ]). '.';
            return ResultUtil::errorResult($errorMessage,Response::HTTP_BAD_REQUEST);
        }

        return ResultUtil::successResult([
            'foundOpenDeclares' => $openCount,
            'openDeclaresResent' => $resentCount
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resendAllCreateBirths(Request $request)
    {
        $loggedInUser = $this->getUser();
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::DEVELOPER);
        if(!$isAdmin) { return AdminValidator::getStandardErrorResponse(); }

        $requestMessages = $this->getManager()->getRepository(DeclareBirth::class)->findBy(
            [
                'requestState' => RequestStateType::OPEN,
                'isRvoMessage' => true,
            ]);

        $openCount = count($requestMessages);
        $resentCount = 0;

        if ($openCount > 0) {
            $logs = ActionLogWriter::createBirth($this->getManager(), $requestMessages, new ArrayCollection(), $loggedInUser);

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

        return ResultUtil::successResult([
            'foundOpenDeclares' => $openCount,
            'openDeclaresResent' => $resentCount
        ]);
    }



    public function revokeBirth(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $content = RequestUtil::getContentAsArrayCollection($request);
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $statusCode = Response::HTTP_PRECONDITION_REQUIRED;
        $litterId = null;

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        if (!key_exists('litter_id', $content->toArray())) {
            throw new PreconditionRequiredHttpException("Mandatory Litter Id not given.");
        }

        $litterId = $content['litter_id'];
        /** @var Litter $litter */
        $litter = $this->getManager()->getRepository(Litter::class)->findOneBy(array ('id' => $litterId));

        if (!$litter) {
            throw new PreconditionRequiredHttpException("No litter was found");
        }

        $childrenToRemove = [];
        $stillbornsToRemove = [];

        $useRvoLogic = $location->isDutchLocation();

        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::ADMIN);

        if (!$isAdmin) {
            //Check if birth registration is within a time span of maxMonthInterval from now,
            //then, and only then, the revoke and thus deletion of child animal is allowed
            foreach ($litter->getChildren() as $child) {
                $dateInterval = $child->getDateOfBirth()->diff(new \DateTime());

                if($dateInterval->y > 0 || $dateInterval->m > self::REVOKE_MAX_MONTH_INTERVAL) {
                    throw new PreconditionRequiredHttpException($child->getUlnCountryCode() .$child->getUlnNumber() . " heeft een geregistreerde geboortedatum dat langer dan "
                        .self::REVOKE_MAX_MONTH_INTERVAL ." maand geleden is, zodoende is het niet geoorloofd om de melding in te trekken en daarmee de geboorte van het dier ongedaan te maken.");
                }
            }
        }

        //Remove still born childs
        foreach ($litter->getStillborns() as $stillborn) {
            $this->getManager()->remove($stillborn);
            $stillbornsToRemove[] = $stillborn;
        }

        $workerMessageBodyForRevoke = WorkerTaskUtil::createResultTableMessageBodyForBirthRevoke($litter);

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
                foreach ([
                             $child->getDeaths(),
                             $child->getDepartures(),
                             $child->getExports(),
                             $child->getArrivals(),
                             $child->getDeclareWeights(),
                             $child->getTagReplacements(),
                         ] as $declaresToRemove) {
                    /** @var DeclareLoss|DeclareDepart|DeclareExport $declareToRemove */
                    foreach($declaresToRemove as $declareToRemove) {
                        if( $declareToRemove instanceof DeclareWeight
                            || $declareToRemove->getRequestState() === RequestStateType::REVOKED
                            || $declareToRemove->getRequestState() === RequestStateType::FAILED
                        ) {

                            if ($declareToRemove instanceof DeclareBase) {
                                foreach ($declareToRemove->getResponses() as $response) {
                                    $this->getManager()->remove($response);
                                }
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
                            } elseif($declareToRemove instanceof DeclareWeight) {
                                $declareType = 'gewichtmelding';
                            }

                            throw new PreconditionRequiredHttpException('Er bestaat nog een '.$declareType.' die niet is ingetrokken voor dit dier '.$child->getUln().' op ubn: '.$declareToRemove->getUbn());
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
            }

            $this->getManager()->flush();

            /** @var Animal $child */
            foreach ($childrenToRemove as $child)
            {
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

            throw new PreconditionRequiredHttpException($errorMessage);
        }


        $this->updateResultTableValuesByWorkerMessageBodyLitter($workerMessageBodyForRevoke,
            $location->isDutchLocation(), true
        );

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
        $customResponse = null;

        if($succeeded) {
            $litter->setRevokedStatus();
            $litter->setMate(null);
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
                        /*
                         * Only successful RVO responses contain messageNumbers and can be revoked by a RevokeDeclare
                         *
                         * DO NOT persist a response here in case of for example a non-RVO revoke.
                         * This will cause a duplicate key violation on the declare_base_response table.
                         * Send a response a json to the worker instead, to be persisted in the worker.
                         */
                        if ($declareBirth->isRvoMessage() &&
                            $declareBirthResponse->getMessageNumber() != null
                        ) {
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
            $statusCode = Response::HTTP_OK;
            $message = 'OK';

            $missingMessages = $declareBirthCount-$declareBirthResponseCount;
            if ($declareBirthCount > $declareBirthResponseCount) {
                if ($useRvoLogic) {
                    $message = 'There are '.$declareBirthCount.' declareBirths found for the litter, which are missing '.$missingMessages.' responses';
                    $statusCode = Response::HTTP_PRECONDITION_REQUIRED;
                }
                // A non-RVO birth-litter might be revoked, before the response has been processed
            } elseif($declareBirthCount == 0 && $litter->getBornAliveCount() != 0) {
                $message = 'The litter does not contain any declareBirths';
                $statusCode = Response::HTTP_PRECONDITION_REQUIRED;
            }

            ActionLogWriter::revokeLitter($this->getManager(), $litter, $loggedInUser, $client);

            $customResponse = new JsonResponse(array(Constant::RESULT_NAMESPACE => [
                'code' => $statusCode,
                'revokes' => $revokeMessages,
                'message' => $message,
            ]), $statusCode);
        }

        $this->removeAnimalCacheOfRemovedChildren($workerMessageBodyForRevoke);

        $this->updateResultTableValuesByWorkerMessageBodyLitter($workerMessageBodyForRevoke,
            $location->isDutchLocation(), true
        );

        if ($customResponse instanceof JsonResponse) {
            return $customResponse;
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
        $content = RequestUtil::getContentAsArrayCollection($request);
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
            $mother = $this->getManager()->getRepository(Ewe::class)->findOneBy(array('ulnCountryCode'=>$motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
        }

        if(!$mother) {
            $statusCode = Response::HTTP_PRECONDITION_REQUIRED;
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
        $otherCandidateFathers = $this->getManager()->getRepository(Animal::class)
            ->getLiveStock($location, $this->getCacheService(), $this->getBaseSerializer(),true, Ram::class);
        $filteredOtherCandidateFathers = [];
        $suggestedCandidateFathers = [];
        $suggestedCandidateFatherIds = [];

        /** @var Animal $animal */
        foreach ($candidateFathers as $animal) {
            $suggestedCandidateFatherIds['id'] = $animal->getId();
            $suggestedCandidateFathers[] = $this->getAnimalResult($animal, $location);
        }

        /** @var Ram $animal */
        foreach ($otherCandidateFathers as $animal) {
            if(!array_key_exists($animal->getId(), $suggestedCandidateFatherIds)
            && BirthService::isFatherDateOfBirthValid($animal, $dateOfBirth)) {
                $filteredOtherCandidateFathers[] = $this->getAnimalResult($animal, $location);
            }
        }

        $filteredOtherCandidateFathersIds = null;

        $result['suggested_candidate_fathers'] = $suggestedCandidateFathers;
        $result['other_candidate_fathers'] = $filteredOtherCandidateFathers;

        return ResultUtil::successResult($result);
    }


    /**
     * @param Ram $father
     * @param \DateTime $dateOfBirthOfChild
     * @return bool
     */
    public static function isFatherDateOfBirthValid($father, $dateOfBirthOfChild): bool
    {
        if (!($father instanceof Ram)
            || $father->getDateOfBirth() === null
            || $father->getDateOfBirth() >= $dateOfBirthOfChild
        ) {
            return false;
        }

        return TimeUtil::getAgeMonths($dateOfBirthOfChild, $father->getDateOfBirth()) >= BirthService::MIN_FATHER_AGE_AT_BIRTH_IN_MONTHS;
    }


    /**
     * @param \DateTime $newLitterDate
     * @param \DateTime $surrogateMotherOffsetDate
     * @param \DateTime $surrogateMotherLitterDate
     * @param Animal $animal
     * @return bool
     * @throws Exception
     */
    private function isSurrogateMotherDateValid(\DateTime $newLitterDate, \DateTime $surrogateMotherOffsetDate, \DateTime $surrogateMotherLitterDate, Animal $animal)
    {
        $diffNewLitterDateAndSurrogateMotherLitterDate = TimeUtil::getDaysBetween($surrogateMotherLitterDate, $newLitterDate);
        $isValidSurrogateByLitterBornRightBefore = (
            0 <= $diffNewLitterDateAndSurrogateMotherLitterDate
            &&
            $diffNewLitterDateAndSurrogateMotherLitterDate <= self::SURROGATE_MOTHER_MAX_BIRTH_OFFSET_FROM_NEW_BIRTH
        );

        return
            abs(TimeUtil::getDaysBetween($newLitterDate, $surrogateMotherOffsetDate)) > self::MINIMUM_DAYS_BETWEEN_BIRTHS ||
            $isValidSurrogateByLitterBornRightBefore ||
            TimeUtil::getDaysBetween($animal->getDateOfBirth(), new \DateTime()) >= 180
        ;
    }


    /**
     * @param Request $request
     * @param string $uln
     * @return JsonResponse
     * @throws Exception
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
            $mother = $this->getManager()->getRepository(Ewe::class)->findOneBy(array ('ulnCountryCode' => $motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
        }

        if(!$mother) {
            $statusCode = Response::HTTP_PRECONDITION_REQUIRED;
            return new JsonResponse(
                array(
                    Constant::RESULT_NAMESPACE => array(
                        'code'=> $statusCode,
                        'message'=> "Moeder met opgegeven ULN: " .$motherUlnCountryCode . $motherUlnNumber ." is niet gevonden."
                    )
                ), $statusCode
            );
        }

        $content = RequestUtil::getContentAsArrayCollection($request);
        if($content->containsKey('date_of_birth')) {
            $dateOfBirth = new \DateTime($content->get('date_of_birth'));
        } else {
            $dateOfBirth = new \DateTime();
        }

        $suggestedCandidatesResult = [];
        $otherCandidatesResult = [];
        $result = [];

        $surrogateMotherCandidates = $this->getManager()->getRepository(DeclareBirth::class)->getCandidateSurrogateMothers($location, $mother);

        $offsetDateFromNow = $dateOfBirth->modify('-' . self::SURROGATE_MOTHER_OFFSET_DAYS .'days');
        /** @var Ewe $animal */
        foreach ($surrogateMotherCandidates as $animal) {

            //Check if surrogate mother candidate has given birth to children within the last 6 months

            /** @var Litter $litter */
            $isSurrogateByLitterData = false;

            foreach ($animal->getLitters() as $litter) {
                if($litter->getStatus() !== RequestStateType::COMPLETED && $litter->getStatus() !== RequestStateType::IMPORTED) {
                    continue;
                }

                //Add as a true candidate surrogate to list
                if(
                    $this->isSurrogateMotherDateValid($dateOfBirth, $offsetDateFromNow, $litter->getLitterDate(), $animal)
                ) {
                    $suggestedCandidatesResult[] = $this->getAnimalResult($animal, $location);
                    $isSurrogateByLitterData = true;
                    break;
                }
            }

            if ($isSurrogateByLitterData) {
                continue;
            }

            if($animal->getChildren()->count() == 0) {
                if(self::SHOW_OTHER_SURROGATE_MOTHERS) {
                    $otherCandidatesResult[] = $this->getAnimalResult($animal, $location);
                }
                continue;
            }

            $children = $animal->getChildren();
            $addToOtherCandidates = true;

            /** @var Animal $child */
            foreach ($children as $child) {
                if($child->getDateOfBirth()) {
                    //Add as a true candidate surrogate to list
                    if ($this->isSurrogateMotherDateValid($dateOfBirth, $offsetDateFromNow, $child->getDateOfBirth())) {
                        $suggestedCandidatesResult[] = $this->getAnimalResult($animal, $location);
                        $addToOtherCandidates = false;
                        break;
                    }
                }
            }

            if (!$addToOtherCandidates) {
                continue;
            }

            if(self::SHOW_OTHER_SURROGATE_MOTHERS) {
                $otherCandidatesResult[] = $this->getAnimalResult($animal, $location);
            }
        }

        $result['suggested_candidate_surrogates'] = $suggestedCandidatesResult;
        $result['other_candidate_surrogates'] = $otherCandidatesResult;

        return ResultUtil::successResult($result);
    }


    /**
     * @param Animal $animal
     * @param Location $location
     * @return array
     */
    private function getAnimalResult(Animal $animal, Location $location)
    {
        return [
            JsonInputConstant::ULN_COUNTRY_CODE => $animal->getUlnCountryCode(),
            JsonInputConstant::ULN_NUMBER => $animal->getUlnNumber(),
            JsonInputConstant::PEDIGREE_COUNTRY_CODE => $animal->getPedigreeCountryCode(),
            JsonInputConstant::PEDIGREE_NUMBER => $animal->getPedigreeNumber(),
            JsonInputConstant::WORK_NUMBER => $animal->getAnimalOrderNumber(),
            JsonInputConstant::COLLAR => $animal->getCollarColor()." ".$animal->getCollarNumber(),
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


    public function getCandidateMothers(Request $request) {
        $content = RequestUtil::getContentAsArrayCollection($request);
        $dateOfBirth = new \DateTime();

        if(key_exists('date_of_birth', $content->toArray())) {
            $dateOfBirth = new \DateTime($content["date_of_birth"]);
        }

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        $suggestedCandidatesResult = [];
        $otherCandidatesResult = [];
        $result = [];

        $motherCandidates = $this->getManager()->getRepository(Animal::class)
            ->getCandidateMothersForBirth($location, $this->getCacheService(), $this->getBaseSerializer())
        ;

        $result['suggested_candidate_mothers'] = $suggestedCandidatesResult;
        $result['other_candidate_mothers'] = $otherCandidatesResult;

        //Animal has no registered matings, thus it is not a true candidate
        /** @var Ewe $animal */
        foreach ($motherCandidates as $animal) {

            if($animal->getMatings()->count() === 0) {
                if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                    $otherCandidatesResult[] = $this->getAnimalResult($animal, $location);
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
                    $daysbetweenCurrentBirthAndPreviousBirths = abs(TimeUtil::getDaysBetween($child->getDateOfBirth(), $dateOfBirth));

                    if(!($daysbetweenCurrentBirthAndPreviousBirths >= self::MINIMUM_DAYS_BETWEEN_BIRTHS)) {
                        $checkAnimalForMatings = false;
                        break;
                    }
                }
            }

            //animal has given birth within the last 167 days, thus it is not a true candidate
            if(!$checkAnimalForMatings) {
                if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                    $otherCandidatesResult[] = $this->getAnimalResult($animal, $location);
                }
                continue;
            }

            $matings = $animal->getMatings();

            /** @var Mate $mating */
            foreach ($matings as $mating) {
                if ($mating->getRequestState() === RequestStateType::REVOKED) {
                    if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                        $otherCandidatesResult[] = $this->getAnimalResult($animal, $location);
                    }
                    continue;
                }

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
                    $suggestedCandidatesResult[] = $this->getAnimalResult($animal, $location);
                    $addToOtherCandidates = false;
                    break;
                }
            }

            if (!$addToOtherCandidates) {
                continue;
            }

            if(self::SHOW_OTHER_CANDIDATE_MOTHERS) {
                $otherCandidatesResult[] = $this->getAnimalResult($animal, $location);
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
        $messageId = RequestUtil::getContentAsArrayCollection($request)->get('message_id');
        $taskType = 'DECLARE_BIRTH';
        $jsonMessage = $request->getContent();

        $declareBirthResponse = WorkerTaskUtil::deserializeMessageToDeclareBirthResponse($request, $this->irSerializer);

        $message = 'Message is not a DeclareBirthResponse';
        $statusCode = Response::HTTP_PRECONDITION_REQUIRED;
        if($declareBirthResponse instanceof DeclareBirthResponse) {
            $sendToQresult = $this->internalQueueService
                ->sendDeclareResponse($jsonMessage, $taskType, $messageId);

            $statusCode = $sendToQresult['statusCode'];
            $message = $jsonMessage;
        }

        return new JsonResponse($message,$statusCode);
    }


    /**
     * @param DeclareBirth $birth
     * @return array|mixed
     */
    public function runDeclareBirthWorkerLogic(DeclareBirth $birth)
    {
        if ($birth->isRvoMessage()) {
            //Send it to the queue and persist/update any changed state to the database
            return $this->sendMessageObjectToQueue($birth);
        }

        // Note animal and litter have already been set before
        $response = new DeclareBirthResponse();
        $response->setDeclareBirthIncludingAllValues($birth);
        $response->setSuccessValues();

        DeclareProcessorBase::sendResponseToWorkerQueue(
            $this->getBaseSerializer(),
            $this->internalQueueService,
            $response
        );

        $birth->setFinishedRequestState();
        $this->removeReservedTag($birth);

        $this->getManager()->persist($birth);

        return $this->getDeclareMessageArray($birth, false);
    }


    /**
     * @param DeclareBirth $birth
     */
    private function removeReservedTag(DeclareBirth $birth)
    {
        if (!$birth->getAnimal()) {
            return;
        }

        $tag = $this->getManager()->getRepository(Tag::class)->findOneBy([
           'ulnCountryCode' => $birth->getAnimal()->getUlnCountryCode(),
           'ulnNumber' => $birth->getAnimal()->getUlnNumber(),
           'tagStatus' => TagStateType::RESERVED
        ]);

        if ($tag) {
            $this->getManager()->remove($tag);
        }
    }


    private function updateLitterStatus(Litter $litter, bool $isRvoMessage)
    {
        if ($isRvoMessage) {
            return;
        }

        $hasIncompleteBirths = false;
        foreach ($litter->getDeclareBirths() as $birth) {
            if ($birth->getRequestState() === RequestStateType::OPEN) {
                $hasIncompleteBirths = true;
                break;
            }
        }

        if (!$hasIncompleteBirths) {
            $litter->setFinishedStatus();
            $this->getManager()->persist($litter);
            $this->getManager()->flush();
        }
    }

    private function generateInbreedingCoefficients(Litter $litter) {
        if ($litter->hasBothParentIds()) {
            $parentIdsPair = new ParentIdsPair(
                $litter->getAnimalFather()->getId(),
                $litter->getAnimalMother()->getId()
            );

            $this->inbreedingCoefficientUpdaterService->generateInbreedingCoefficients(
                [$parentIdsPair],
                false
            );

            $this->inbreedingCoefficientUpdaterService->matchAnimalsAndLitters(
                $litter->getChildrenIds(),
                [$litter->getId()]
            );
        }
    }

    /**
     * @param DeclareBirth[] $births
     * @param bool $isRvoMessage
     */
    private function updateResultTableValuesByBirthRequests($births, bool $isRvoMessage)
    {
        if ($isRvoMessage) {
            //Send workerTask to update resultTable records of parents and children
            $this->sendTaskToQueue($this->internalQueueService, WorkerTaskUtil::createResultTableMessageBodyByBirthRequests($births));
            return;
        }

        $animalIds = [];
        foreach ($births as $birth) {
            if ($birth->getAnimal() && $birth->getAnimal()->getId()) {
                $animalIds[$birth->getAnimal()->getId()] = $birth->getAnimal()->getId();
            }

            if ($birth->getLitter()){
                $father = $birth->getLitter()->getAnimalFather();
                if ($father && $father->getId()) {
                    $animalIds[$father->getId()] = $father->getId();
                }

                $mother = $birth->getLitter()->getAnimalMother();
                if ($mother && $mother->getId()) {
                    $animalIds[$mother->getId()] = $mother->getId();
                }
            }
        }

        $this->directlyUpdateResultTableValuesByAnimalIds(array_values($animalIds));
    }


    /**
     * @param WorkerMessageBodyLitter $workerMessageBodyLitter
     * @param bool $isRvoMessage
     * @param bool $onlyUpdateParents
     */
    private function updateResultTableValuesByWorkerMessageBodyLitter(WorkerMessageBodyLitter $workerMessageBodyLitter,
                                                                     bool $isRvoMessage, bool $onlyUpdateParents)
    {
        if ($isRvoMessage) {
            //Send workerTask to update productionValues of parents
            $this->sendTaskToQueue($this->internalQueueService, $workerMessageBodyLitter);
            return;
        }

        if ($onlyUpdateParents) {
            $this->directlyUpdateResultTableValuesByAnimalIds($workerMessageBodyLitter->getParentIds());
        } else {
            $this->directlyUpdateResultTableValuesByAnimalIds($workerMessageBodyLitter->getAllAnimalIds());
        }
    }


    /**
     * @param array $animalIds
     */
    private function directlyUpdateResultTableValuesByAnimalIds(array $animalIds)
    {
        AnimalCacher::cacheByAnimalIds($this->getConnection(), $animalIds);
    }


    /**
     * @param WorkerMessageBodyLitter $workerMessageBodyLitter
     * @throws DBALException
     */
    private function removeAnimalCacheOfRemovedChildren(WorkerMessageBodyLitter $workerMessageBodyLitter)
    {
        AnimalCacher::removeOrphanedRecordsByAnimalIds($this->getConnection(), $workerMessageBodyLitter->getChildrenIds());
    }

    /**
     * Using sql queries, because the EntityManager is closed after an Exception.
     * It is also possible to just create a new EntityManager.
     *
     * @param array $reservedTagUlns
     * @param int $clientId
     * @param int $locationId
     * @throws DBALException
     */
    private function rollBackTags(array $reservedTagUlns, int $clientId, int $locationId)
    {
        if (empty($reservedTagUlns)) {
            return;
        }

        $idKey = JsonInputConstant::ID;
        $ulnKey = JsonInputConstant::ULN;
        $sql = "SELECT $idKey, CONCAT(uln_country_code, uln_number) as $ulnKey FROM tag WHERE CONCAT(uln_country_code,uln_number) IN (
                     ".SqlUtil::getFilterListString($reservedTagUlns, true)."
                )";
        $results = $this->getConnection()->query($sql)->fetchAll();
        $tagIds = empty($results) ? [] :
            SqlUtil::getSingleValueGroupedSqlResults($idKey, $results,true,true);

        if (!empty($tagIds)) {
            $sql = "UPDATE tag SET tag_status = 'UNASSIGNED'
                WHERE id IN (
                    ".SqlUtil::getFilterListString($tagIds, false)."
                )";
            SqlUtil::updateWithCount($this->getConnection(), $sql);
        }

        $ulnsOfExistingTags = empty($results) ? [] :
            SqlUtil::getSingleValueGroupedSqlResults($ulnKey, $results,false,true);

        $ulnsOfTagsToInsert = [];
        foreach ($reservedTagUlns as $reservedTagUln) {
            if (!key_exists($reservedTagUln, $ulnsOfExistingTags)) {
                $ulnsOfTagsToInsert[] = $reservedTagUln;
            }
        }

        if (!empty($ulnsOfTagsToInsert)) {
            DoctrineUtil::updateTableSequence($this->getConnection(), [Tag::getTableName()]);

            $prefix = '';
            $insertValuesString = '';
            foreach ($ulnsOfTagsToInsert as $ulnOfTagToInsert) {
                $ulnParts = Utils::getUlnFromString($ulnOfTagToInsert);
                $ulnNumber = $ulnParts[JsonInputConstant::ULN_NUMBER];
                $ulnCountryCode =  $ulnParts[JsonInputConstant::ULN_COUNTRY_CODE];
                $animalOrderNumber = StringUtil::getLast5CharactersFromString($ulnNumber);

                $insertValuesString .= $prefix."(".$clientId
                    . ",'" . TagStateType::UNASSIGNED . "'"
                    . ",'" . $animalOrderNumber . "'"
                    .',NOW()'
                    .",'".$ulnCountryCode."'"
                    .",'".$ulnNumber."'"
                    .",".$locationId.")"
                ;
                $prefix = ',';
            }

            $sql = "INSERT INTO tag 
                    (owner_id, tag_status, animal_order_number, order_date, uln_country_code, uln_number, location_id)
                    VALUES ".$insertValuesString;
            SqlUtil::updateWithCount($this->getConnection(), $sql);
        }
    }


    /**
     * Using sql queries, because the EntityManager is closed after an Exception.
     * It is also possible to just create a new EntityManager.
     *
     * @param array $animalIds
     * @throws DBALException
     */
    private function removeAnimals(array $animalIds)
    {
        if (empty($animalIds) || !ArrayUtil::containsOnlyDigits($animalIds)) {
            return;
        }
        $sql = "DELETE FROM animal WHERE id IN (".SqlUtil::getFilterListString($animalIds, false).")";
        SqlUtil::updateWithCount($this->getConnection(), $sql);
    }

    /**
     * Using sql queries, because the EntityManager is closed after an Exception.
     * It is also possible to just create a new EntityManager.
     *
     * @param $litterId
     * @throws DBALException
     */
    private function removeLitter($litterId)
    {
        if (empty($litterId)) {
            return;
        }
        $sql = "DELETE FROM declare_nsfo_base WHERE id = ".$litterId;
        SqlUtil::updateWithCount($this->getConnection(), $sql);
    }
}
