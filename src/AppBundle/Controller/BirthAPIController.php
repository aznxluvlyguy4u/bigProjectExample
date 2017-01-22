<?php

namespace AppBundle\Controller;

use AppBundle\Cache\AnimalCacher;
use AppBundle\Component\MessageBuilderBase;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalCache;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\DeclareBirth;
use AppBundle\Entity\DeclareBirthResponse;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Location;
use AppBundle\Entity\MateRepository;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\TagRepository;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Output\DeclareBirthResponseOutput;
use AppBundle\Util\ActionLogWriter;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
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
    /**
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

        $repository = $this->getDoctrine()->getRepository(Litter::class);
        $litter = $repository->findOneBy(['id' => $litterId, 'ubn' => $location->getUbn()]);
        $result = DeclareBirthResponseOutput::createBirth($litter, $litter->getDeclareBirths());
        
        if($result instanceof JsonResponse) {
            return $result;
        }

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
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

        //Clear cache for this location, to reflect changes on the livestock
        $cacheId = AnimalRepository::LIVESTOCK_CACHE_ID .$location->getId();
        $this->getRedisClient()->del('[' .$cacheId .'][1]');

        return new JsonResponse($result, 200);
    }

    /**
     * Revoke DeclareBirth request
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

        $childrenToRemove = [];
        $stillbornsToRemove = [];
        $maxMonthInterval = 1;

        //Check if birth registration is within a time span of maxMonthInterval from now,
        //then, and only then, the revoke and thus deletion of child animal is allowed
        foreach ($litter->getChildren() as $child) {
            $dateInterval = $child->getDateOfBirth()->diff(new \DateTime());

            if($dateInterval->y > 0 || $dateInterval->m >= $maxMonthInterval) {
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

        //Add check to see if revoke is allowed within MAX_TIME_INTERVAL
        //Remove still born childs
        foreach ($litter->getStillborns() as $stillborn) {
            $manager->remove($stillborn);
            $stillbornsToRemove[] = $stillborn;
        }

        //Remove alive child animal
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

            //Remove animalCache
            //$animalCache = $manager->getRepository(AnimalCache::class)->findOneBy(['animalId' => $child->getId()]);

//            if($animalCache){
//                $manager->remove($animalCache);
//                $manager->flush();
//            }

            //Restore tag if it does not exist
            /** @var Tag $tagToRestore */
            $tagToRestore = null;

            /** @var TagRepository $tagRepository */
            $tagRepository = $manager->getRepository(Tag::getClassName());
            $tagToRestore = $tagRepository->findByUlnNumberAndCountryCode($child->getUlnCountryCode(), $child->getUlnNumber());

            if ($tagToRestore) {
                $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
            } else {
                $tagToRestore = new Tag();
                $tagToRestore->setLocation($location);
                $tagToRestore->setOrderDate(new \DateTime());
                $tagToRestore->setOwner($client);
                $tagToRestore->setTagStatus(TagStateType::UNASSIGNED);
                $tagToRestore->setUlnCountryCode($child->getUlnCountryCode());
                $tagToRestore->setUlnNumber($child->getUlnNumber());
                $tagToRestore->setAnimalOrderNumber($child->getAnimalOrderNumber());
            }

            $manager->persist($tagToRestore);
            $manager->flush();

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

        //Re-retrieve litter, check count
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
            //Create revoke request for every declareBirth request
            if ($litter->getDeclareBirths()->count() > 0) {
                foreach ($litter->getDeclareBirths() as $declareBirth) {
                    $declareBirthResponse = $this->getEntityGetter()
                      ->getResponseDeclarationByMessageId($declareBirth->getMessageId());

                    if ($declareBirthResponse) {
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
            //Clear cache for this location, to reflect changes on the livestock
            $cacheId = AnimalRepository::LIVESTOCK_CACHE_ID .$location->getId();
            $this->getRedisClient()->del('[' .$cacheId .'][1]');

            return new JsonResponse(array(Constant::RESULT_NAMESPACE => $revokeMessages), 200);
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
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{uln}/candidate-fathers")
     * @Method("GET")
     */
    public function getCandidateFathers(Request $request, $uln) {
        $client = $this->getAuthenticatedUser($request);
        $motherUlnCountryCode = null;
        $motherUlnNumber = null;

        if($uln) {
            $motherUlnCountryCode = mb_substr($uln, 0, 2);
            $motherUlnNumber = substr($uln, 2);
        }

        /** @var Location $location */
        $location = $this->getSelectedLocation($request);
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        /** @var MateRepository $mateRepository */
        $mateRepository = $this->getDoctrine()->getRepository(Constant::MATE_REPOSITORY);

        /** @var Ewe $mother */
        $mother = $animalRepository->findOneBy(array('ulnCountryCode'=>$motherUlnCountryCode, 'ulnNumber' => $motherUlnNumber));
        $result = [];

        if($mother) {
            $fathers = $mateRepository->getMatingFathersOfMother($location , $mother);

            /** @var Animal $animal */
            foreach ($fathers as $animal) {

                $result[] = [
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

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }

    /**
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/{uln}/candidate-surrogates")
     * @Method("GET")
     */
    public function getCandidateSurrogates(Request $request, $uln) {
        $client = $client = $this->getAuthenticatedUser($request);
        /** @var Location $location */
        $location = $this->getSelectedLocation($request);
        //AnimalCacher::cacheAnimalsBySqlInsert($this->getDoctrine()->getManager(), null, $location->getId());
        /** @var AnimalRepository $animalRepository */
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        $livestockArray = $animalRepository->getLiveStock($location);

        $result = [];

        foreach ($livestockArray as $item) {
            if ($item['gender'] == GenderType::FEMALE) {
                if($item['uln_number'] != substr($uln, 2)) {
                    $result[] = $item;
                }
            }
        }

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $result), 200);
    }
}