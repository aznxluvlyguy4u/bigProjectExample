<?php

namespace AppBundle\Controller;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\MessageBuilderBase;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalCache;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthRepository;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareDepart;
use AppBundle\Entity\DeclareExport;
use AppBundle\Entity\DeclareLoss;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\Mate;
use AppBundle\Entity\MateRepository;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareBirthResponseOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ExceptionUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\WorkerTaskUtil;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
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
    const SHOW_OTHER_CANDIDATE_MOTHERS = false;
    const SHOW_OTHER_SURROGATE_MOTHERS = false;

    /**
     * Get all births for a given litter
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get all births for a given litter"
     * )
     * @param Request $request the request object
     * @param String $litterId
     * @return JsonResponse
     * @Route("/{litterId}")
     * @Method("GET")
     */
    public function getBirth(Request $request, $litterId)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        if(!$location) {
          return Validator::createJsonResponse('UBN kan niet gevonden worden', 428);
        }

        $repository = $this->getDoctrine()->getRepository(Litter::class);
        $litter = $repository->findOneBy(['id' => $litterId, 'ubn' => $location->getUbn()]);

        if($litter instanceof Litter) {
          $result = DeclareBirthResponseOutput::createBirth($litter, $litter->getDeclareBirths());
        } else {
            $result = Validator::createJsonResponse('Geen worp gevonden voor gegeven worpId en ubn', 428);
        }

        if($result instanceof JsonResponse) {
          return $result;
        }

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
    * Retrieve all births for a given location
    *
    * @ApiDoc(
    *   section = "Births",
    *   requirements={
    *     {
    *       "name"="AccessToken",
    *       "dataType"="string",
    *       "requirement"="",
    *       "description"="A valid accesstoken belonging to the user that is registered with the API"
    *     }
    *   },
    *   resource = true,
    *   description = "Retrieve all births for a given location"
    * )
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("GET")
    */
    public function getHistoryBirths(Request $request)
    {
        $this->getAuthenticatedUser($request);
        $location = $this->getSelectedLocation($request);

        $em = $this->getDoctrine()->getManager();
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

        $birthDeclarations = $em->getConnection()->query($sql)->fetchAll();

        $result = DeclareBirthResponseOutput::createHistoryResponse($birthDeclarations);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
    * Create a new birth of an animal
    *
    * @ApiDoc(
    *   section = "Births",
    *   requirements={
    *     {
    *       "name"="AccessToken",
    *       "dataType"="string",
    *       "requirement"="",
    *       "description"="A valid accesstoken belonging to the user that is registered with the API"
    *     }
    *   },
    *   resource = true,
    *   description = " Create a new birth of an animal"
    * )
    * Create a new DeclareBirth request
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("POST")
    */
    public function createBirth(Request $request)
    {
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);
        $location = $this->getSelectedLocation($request);

        $requestMessages = $this->getRequestMessageBuilder()
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

        //Creating request succeeded, send to Queue
        foreach ($requestMessages as $requestMessage) {
            //First persist requestmessage, before sending it to the queue
            $this->persist($requestMessage);

            //Send it to the queue and persist/update any changed state to the database
            $result[] = $this->sendMessageObjectToQueue($requestMessage);
        }


        //Send workerTask to update resultTable records of parents and children
        $this->sendTaskToQueue(WorkerTaskUtil::createResultTableMessageBodyByBirthRequests($requestMessages));

        //Clear cache for this location, to reflect changes on the livestock
        $this->clearLivestockCacheForLocation($location);


        return new JsonResponse($result, 200);
    }


    /**
     * Resend OPEN birth declarations to RVO that are missing a response message.
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Resend OPEN birth declarations to RVO that are missing a response message"
     * )
     * Create a new DeclareBirth request
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/resend")
     * @Method("POST")
     */
    public function resendCreateBirth(Request $request)
    {
        $loggedInUser = $this->getLoggedInUser($request);
        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::DEVELOPER);
        if(!$isAdmin) { return AdminValidator::getStandardErrorResponse(); }

        $requestMessages = $this->getDoctrine()->getRepository(DeclareBirth::class)
            ->findBy(['requestState' => RequestStateType::OPEN]);

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

        return new JsonResponse(['DeclareBirth' => ['found open declares' => $openCount, 'open declares resent' => $resentCount]], 200);
    }



    /**
     * Revoke a birth of an animal
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Revoke a birth of an animal"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/revoke")
     * @Method("POST")
     */
    public function revokeBirth(Request $request) {
        $manager = $this->getDoctrine()->getManager();

        $location = $this->getSelectedLocation($request);
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);
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
        $repository = $this->getDoctrine()->getRepository(Litter::class);
        /** @var Litter $litter */
        $litter = $repository->findOneBy(array ('id' => $litterId));

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
        $maxMonthInterval = 6;

        //Check if birth registration is within a time span of maxMonthInterval from now,
        //then, and only then, the revoke and thus deletion of child animal is allowed
        foreach ($litter->getChildren() as $child) {
            $dateInterval = $child->getDateOfBirth()->diff(new \DateTime());

            if($dateInterval->y > 0 || $dateInterval->m > $maxMonthInterval) {
                return new JsonResponse(
                  array (
                    Constant::RESULT_NAMESPACE => array (
                      'code' => $statusCode,
                      "message" => $child->getUlnCountryCode() .$child->getUlnNumber() . " heeft een geregistreerde geboortedatum dat langer dan "
                        .$maxMonthInterval ." maand geleden is, zodoende is het niet geoorloofd om de melding in te trekken en daarmee de geboorte van het dier ongedaan te maken.",
                    )
                  ), $statusCode);
            }
        }
        
        //Remove still born childs
        foreach ($litter->getStillborns() as $stillborn) {
            $manager->remove($stillborn);
            $stillbornsToRemove[] = $stillborn;
        }

        //Send workerTask to update productionValues of parents
        $this->sendTaskToQueue(WorkerTaskUtil::createResultTableMessageBodyForBirthRevoke($litter));

        //Remove alive child animal
        try {
            /** @var Animal $child */
            foreach ($litter->getChildren() as $child) {

                $childrenToRemove[] = $child;

                //Remove animal residence
                $residenceHistory = $child->getAnimalResidenceHistory();
                foreach ($residenceHistory as $residence) {
                    $manager->remove($residence);
                }

                //Remove weights
                $weights = $child->getWeightMeasurements();
                foreach ($weights as $weight) {
                    $manager->remove($weight);
                }

                //Remove tail lengths
                $tailLengths = $child->getTailLengthMeasurements();
                foreach ($tailLengths as $tailLength) {
                    $manager->remove($tailLength);
                }

                //Remove bodyfats
                $bodyFats = $child->getBodyFatMeasurements();
                foreach ($bodyFats as $bodyFat) {
                    $manager->remove($bodyFat);
                }

                //Remove exteriors
                $exteriors = $child->getExteriorMeasurements();
                foreach ($exteriors as $exterior) {
                    $manager->remove($exterior);
                }

                //Remove muscleThickness
                $muscleThicknesses = $child->getMuscleThicknessMeasurements();
                foreach ($muscleThicknesses as $muscleThickness) {
                    $manager->remove($muscleThickness);
                }

                //Remove breedCodes
                if ($child->getBreedCodes()) {
                    $breedCodes = $child->getBreedCodes();

                    foreach ($breedCodes->getCodes() as $codes) {
                        $manager->remove($codes);
                    }
                    $child->setBreedCodes(null);
                    $manager->remove($breedCodes);
                }

                //Remove breedset values
                $breedValues = $child->getBreedValuesSets();
                foreach ($breedValues as $breedValue) {
                    $manager->remove($breedValue);
                }

                //Remove gender change history items
                $genderHistories = $child->getGenderHistory();
                foreach ($genderHistories as $genderHistory) {
                    $manager->remove($genderHistory);
                }


                //Remove REVOKED declare losses, exports and departs
                foreach ([$child->getDeaths(), $child->getDepartures(), $child->getExports()] as $declaresToRemove) {
                    /** @var DeclareLoss|DeclareDepart|DeclareExport $declareToRemove */
                    foreach($declaresToRemove as $declareToRemove) {
                        if($declareToRemove->getRequestState() === RequestStateType::REVOKED) {
                            foreach ($declareToRemove->getResponses() as $response) {
                                $manager->remove($response);
                            }
                            $manager->remove($declareToRemove);
                        }
                    }
                }


                if($child->getLatestBreedGrades()) {
                    $manager->remove($child->getLatestBreedGrades());
                }


                $breedValueRepository = $manager->getRepository(BreedValue::class);
                $breedValues = $breedValueRepository->findBy(['animal'=>$child]);
                foreach ($breedValues as $breedValue) {
                    $manager->remove($breedValue);
                }


                //Flush the removes separately
                $manager->flush();

                //Restore tag if it does not exist
                /** @var Tag $tagToRestore */
                $tagToRestore = null;

                /** @var TagRepository $tagRepository */
                $tagRepository = $manager->getRepository(Tag::getClassName());
                $tagToRestore = $tagRepository->findByUlnNumberAndCountryCode($child->getUlnCountryCode(), $child->getUlnNumber());

                if ($tagToRestore) {
                    $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
                    $manager->persist($tagToRestore);
                    $manager->flush();
                } else {
                    $tagToRestore = $tagRepository->restoreTagWithPrimaryKeyCheck($manager, $location, $client, $child->getUlnCountryCode(), $child->getUlnNumber());
                    if($tagToRestore instanceof JsonResponse) { return $tagToRestore; }
                }

                //Remove child from location
                if ($location->getAnimals()->contains($child)) {
                    $location->getAnimals()->removeElement($child);
                    $manager->persist($location);
                }

                $litter->removeChild($child);
                $manager->persist($litter);
                $manager->flush();

                $child->setParentFather(null);
                $child->setParentMother(null);
                $child->setParentNeuter(null);
                $child->setSurrogate(null);

                $manager->persist($child);
                $manager->flush();

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
                                        $manager->persist($declareBirthResponse);

                                    }
                                }
                            }
                            //Remove child animal
                            $declareBirth->setAnimal(null);
                            $manager->persist($declareBirth);
                        }
                    }
                }

                //Remove child animal
                $manager->remove($child);
            }

            $manager->flush();

        } catch (ForeignKeyConstraintViolationException $e) {
            $exceptionMessage = $e->getMessage();
            $this->getLogger()->critical($exceptionMessage);

            $errorMessage = "Voor de kinderen in deze worp zijn nieuwe gegevens geregistreerd, waardoor het niet mogelijk is om deze dieren via een geboortemeldingintrekking te verwijderen.";

            $blockedTable = ExceptionUtil::getBlockedTableInForeignKeyConstraintViolationException($e);
            $referenceTable = ExceptionUtil::getReferenceTableInForeignKeyConstraintViolationException($e);
            if($blockedTable) {
                $errorMessage = $errorMessage.' De geblokkeerde tabel = '.$blockedTable.'.';
            }
            if($referenceTable) {
                $errorMessage = $errorMessage.' De referentie tabel = '.$referenceTable.'.';
            }

            return Validator::createJsonResponse($errorMessage, $statusCode);
        }


        //Send workerTask to update productionValues of parents
        $this->sendTaskToQueue(WorkerTaskUtil::createResultTableMessageBodyForBirthRevoke($litterClone));

        //Clear cache for this location, to reflect changes on the livestock.
        $this->clearLivestockCacheForLocation($location);

        //Re-retrieve litter, check count
        /** @var Litter $litter */
        $litter = $repository->findOneBy(array ('id'=> $litterId));

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

            $manager->persist($litter);
            $manager->flush();

            $revokeMessages = [];
            $declareBirthCount = 0;
            $declareBirthResponseCount = 0;
            //Create revoke request for every declareBirth request
            if ($litter->getDeclareBirths()->count() > 0) {
                foreach ($litter->getDeclareBirths() as $declareBirth) {
                    $declareBirthCount++;
                    $declareBirthResponse = $this->getEntityGetter()
                      ->getResponseDeclarationByMessageId($declareBirth->getMessageId());

                    if ($declareBirthResponse) {
                        $declareBirthResponseCount++;
                        //Only successful responses contain messageNumbers and can be revoked
                        if($declareBirthResponse->getMessageNumber() != null) {
                            $message = new ArrayCollection();
                            $message->set(Constant::MESSAGE_NUMBER_SNAKE_CASE_NAMESPACE, $declareBirthResponse->getMessageNumber());
                            $revokeDeclarationObject = $this->buildMessageObject(RequestType::REVOKE_DECLARATION_ENTITY, $message, $client, $loggedInUser, $location);
                            $this->persist($revokeDeclarationObject);
                            $this->persistRevokingRequestState($revokeDeclarationObject->getMessageNumber());
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

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => [
                'code' => $statusCode,
                'revokes' => $revokeMessages,
                'message' => $message,
            ]), $statusCode);
        }

        return new JsonResponse(
          array(
            Constant::RESULT_NAMESPACE => array (
              'code' => $statusCode,
              "message" => "Failed to revoke and remove all child and stillborn animals ",
            )
          ), $statusCode);
    }

    /**
     * Get a list of suggested candidate fathers based on matings done within 145 + (-12 & +12) days, from now, and all other Rams on current location
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get a list of suggested candidate fathers based on matings done within 145 + (-12 & +12) days, from now and all other Rams on current location"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{uln}/candidate-fathers")
     * @Method("POST")
     */
    public function getCandidateFathers(Request $request, $uln) {
        $content = $this->getContentAsArray($request);
        $dateOfBirth = new \DateTime();

        if(key_exists('date_of_birth', $content->toArray())) {
            $dateOfBirth = new \DateTime($content["date_of_birth"]);
        }

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        /** @var DeclareBirthRepository $declareBirthRepository */
        $declareBirthRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);
        /** @var Ewe $mother */
        $mother = null;
        $motherUlnCountryCode = null;
        $motherUlnNumber = null;

        if($uln) {
            $motherUlnCountryCode = mb_substr($uln, 0, 2);
            $motherUlnNumber = substr($uln, 2);
            $mother = $animalRepository->findOneBy(array('ulnCountryCode'=>$motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
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
        $candidateFathers = $declareBirthRepository->getCandidateFathers($mother, $dateOfBirth);
        $otherCandidateFathers = $animalRepository->getLiveStock($location, true, false, false, Ram::class);
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

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * Get a list of suggested candidate surrogates based on births done within 5,5 months from given date of birth, and all other Ewes on current location
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get a list of suggested candidate surrogates based on births done within 5,5 months from given date of birth, and all other Ewes on current location"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{uln}/candidate-surrogates")
     * @Method("POST")
     */
    public function getCandidateSurrogateMothers(Request $request, $uln) {
        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        /** @var DeclareBirthRepository $declareBirthRepository */
        $declareBirthRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);
        /** @var Ewe $mother */
        $mother = null;
        $motherUlnCountryCode = null;
        $motherUlnNumber = null;

        if($uln) {
            $motherUlnCountryCode = mb_substr($uln, 0, 2);
            $motherUlnNumber = substr($uln, 2);
            $mother = $animalRepository->findOneBy(array ('ulnCountryCode' => $motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
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

        $content = $this->getContentAsArray($request);
        if($content->containsKey('date_of_birth')) {
            $dateOfBirth = new \DateTime($content->get('date_of_birth'));
        } else {
            $dateOfBirth = new \DateTime();
        }

        $suggestedCandidatesResult = [];
        $otherCandidatesResult = [];
        $result = [];

        $surrogateMotherCandidates = $declareBirthRepository->getCandidateSurrogateMothers($location , $mother);

        $offsetDays = 7;
        $minimumDaysIntervalFromNowAndBirth = 167;
        $offsetDateFromNow = $dateOfBirth->modify('-' .$offsetDays .'days');

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
                    if(TimeUtil::getDaysBetween($child->getDateOfBirth(), $offsetDateFromNow) > $minimumDaysIntervalFromNowAndBirth) {
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

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * Get a list of suggested mothers based on matings done within 145 days and all other Ewes on current location
     *
     * @ApiDoc(
     *   section = "Births",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Get a list of suggested mothers based on matings done within 145 days and all other Ewes on current location"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/candidate-mothers")
     * @Method("POST")
     */
    public function getCandidateMothers(Request $request) {
        $content = $this->getContentAsArray($request);
        $dateOfBirth = new \DateTime();

        if(key_exists('date_of_birth', $content->toArray())) {
            $dateOfBirth = new \DateTime($content["date_of_birth"]);
        }

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);

        $suggestedCandidatesResult = [];
        $otherCandidatesResult = [];
        $result = [];

        $motherCandidates = $animalRepository->getLiveStock($location , true, false, false, Ewe::class);

        $result['suggested_candidate_mothers'] = $suggestedCandidatesResult;
        $result['other_candidate_mothers'] = $otherCandidatesResult;

        $pregnancyDays = 145;
        $minimumDaysBetweenBirths = 167;
        $matingDaysOffset = 12;

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

            /** @var DeclareBirthRepository $declareBirthRepository */
            $declareBirthRepository = $this->getDoctrine()->getRepository(DeclareBirth::getClassName());
            $children = $declareBirthRepository->getChildrenOfEwe($animal);

            //Check if Mother has children that are born in the last 5,5 months if so, it is not a true candidate
            /** @var Animal $child */
            foreach ($children as $child) {
                if($child->getDateOfBirth()) {
                    $daysbetweenCurrentBirthAndPreviousBirths = TimeUtil::getDaysBetween($child->getDateOfBirth(), $dateOfBirth);

                    if(!($daysbetweenCurrentBirthAndPreviousBirths >= $minimumDaysBetweenBirths)) {
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
                $lowerboundPregnancyDays = $pregnancyDays - $matingDaysOffset;
                $upperboundPregnancyDays = $pregnancyDays + $matingDaysOffset;

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

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * TODO delete me from both Front-end and API
     * Temporarily endpoint to let catch errors.
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getBirthErrors(Request $request) {
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => []), 200);
    }
}