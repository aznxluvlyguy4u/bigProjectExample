<?php

namespace AppBundle\Controller;

use AppBundle\Component\Modifier\AnimalRemover;
use AppBundle\Component\Modifier\MessageModifier;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Litter;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\TailLength;
use AppBundle\Entity\Weight;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\TagStateType;
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
   * Retrieve a DeclareBirth, found by it's ID.
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
   *   description = "Retrieve a DeclareBirth by given ID",
   *   output = "AppBundle\Entity\DeclareBirth"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareBirth to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareBirthRepository")
   * @Method("GET")
   */
  public function getBirthById(Request $request, $Id)
  {
      $client = $this->getAuthenticatedUser($request);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);

      $export = $repository->getBirthByRequestId($client, $Id);

      return new JsonResponse($export, 200);
  }

  /**
   * Retrieve either a list of all DeclareBirths or a subset of DeclareBirths with a given state-type:
   * {
   *    OPEN,
   *    FINISHED,
   *    FAILED,
   *    CANCELLED
   * }
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
   *        "name"="state",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" DeclareBirths to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareBirths",
   *   output = "AppBundle\Entity\DeclareBirth"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getBirths(Request $request)
  {
      $client = $this->getAuthenticatedUser($request);
      $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
      $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);

      if(!$stateExists) {
          $declareBirths = $repository->getBirths($client);

      } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

          $declareBirths = new ArrayCollection();

          foreach($repository->getBirths($client, RequestStateType::OPEN) as $birth) {
            $declareBirths->add($birth);
          }

          foreach($repository->getBirths($client, RequestStateType::REVOKING) as $birth) {
              $declareBirths->add($birth);
          }
          foreach($repository->getBirths($client, RequestStateType::FINISHED) as $birth) {
              $declareBirths->add($birth);
          }
          
      } else { //A state parameter was given, use custom filter to find subset
          $state = $request->query->get(Constant::STATE_NAMESPACE);
          $declareBirths = $repository->getBirths($client, $state);
      }

      return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareBirths), 200);
  }

    /**
     * Create a new DeclareBirth request
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("/false-birth")
     * @Method("POST")
     */
    public function createFalseBirth(Request $request) {
        $location = $this->getSelectedLocation($request);
        $content = $this->getContentAsArray($request);
        $company = $location->getCompany();

        // Litter
        $litter = new Litter();
        $litter->setLitterDate(new \DateTime($content['date_of_birth']));
        $litter->setIsAbortion($content['is_aborted']);
        $litter->setIsPseudoPregnancy($content['is_pseudo_pregnancy']);
        $litter->setStillbornCount(0);
        $litter->setBornAliveCount(0);
        $litter->setStatus('COMPLETE');

        // Mother
        /** @var Ewe $mother */
        $repository = $this->getDoctrine()->getRepository(Constant::EWE_REPOSITORY);
        $contentMother = $content['mother'];

        if(key_exists('uln_country_code', $contentMother) && key_exists('uln_number', $contentMother)) {
            if ($contentMother['uln_country_code'] != '' && $contentMother['uln_number'] != '') {
                $mother = $repository->findOneBy([
                    'ulnCountryCode' => $contentMother['uln_country_code'],
                    'ulnNumber' => $contentMother['uln_number'],
                    'isAlive' => true
                ]);

                if ($mother == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE MOTHER IS NOT FOUND'
                    ], 428);
                }
            }
        }

        $motherCompany = $mother->getLocation()->getCompany();
        if($company != $motherCompany) {
            return new JsonResponse([
                Constant::CODE_NAMESPACE => 428,
                Constant::MESSAGE_NAMESPACE => 'THE MOTHER IS NOT IN YOUR LIVESTOCK'
            ], 428);
        }

        /** @var Litter $litter */
        foreach($mother->getLitters() as $motherLitter) {
            $litterDate = $motherLitter->getLitterDate()->format('Y-m-d');
            $contentDate = (new \DateTime($content['date_of_birth']))->format('Y-m-d');

            if($litterDate == $contentDate) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE MOTHER ALREADY HAS A LITTER REGISTERED ON THIS DATE'
                ], 428);
            }
        }

        $litter->setAnimalMother($mother);

        // Father
        $repository = $this->getDoctrine()->getRepository(Constant::RAM_REPOSITORY);
        $contentFather = $content['father'];

        $father = null;

        if(key_exists('pedigree_country_code', $contentFather) && key_exists('pedigree_number', $contentFather)) {
            if($contentFather['pedigree_country_code'] != '' && $contentFather['pedigree_number'] != '') {
                $father = $repository->findOneBy([
                    'pedigreeCountryCode' => $contentFather['pedigree_country_code'],
                    'pedigreeNumber' => $contentFather['pedigree_number'],
                    'isAlive' => true
                ]);

                if($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE PEDIGREE OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        if(key_exists('uln_country_code', $contentFather) && key_exists('uln_number', $contentFather)) {
            if ($contentFather['uln_country_code'] != '' && $contentFather['uln_number'] != '') {
                $father = $repository->findOneBy([
                    'ulnCountryCode' => $contentFather['uln_country_code'],
                    'ulnNumber' => $contentFather['uln_number'],
                    'isAlive' => true
                ]);

                if ($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        // Persist Litter
        $this->getDoctrine()->getManager()->persist($litter);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([Constant::RESULT_NAMESPACE => 'ok'], 200);
    }

    /**
    * Create a new DeclareBirth request
    * @param Request $request the request object
    * @return JsonResponse
    * @Route("")
    * @Method("POST")
    */
    public function createBirth(Request $request) {
        $location = $this->getSelectedLocation($request);
        $content = $this->getContentAsArray($request);
        $client = $this->getAuthenticatedUser($request);
        $loggedInUser = $this->getLoggedInUser($request);
        // TODO VALIDATE CONTENT

        // Company
        $company = $location->getCompany();

        // Litter
        $litter = new Litter();
        $litter->setLitterDate(new \DateTime($content['date_of_birth']));
        $litter->setIsAbortion($content['is_aborted']);
        $litter->setIsPseudoPregnancy($content['is_pseudo_pregnancy']);
        $litter->setStatus('INCOMPLETE');

        // Mother
        /** @var Ewe $mother */
        $repository = $this->getDoctrine()->getRepository(Constant::EWE_REPOSITORY);
        $contentMother = $content['mother'];

        if(key_exists('uln_country_code', $contentMother) && key_exists('uln_number', $contentMother)) {
            if ($contentMother['uln_country_code'] != '' && $contentMother['uln_number'] != '') {
                $mother = $repository->findOneBy([
                    'ulnCountryCode' => $contentMother['uln_country_code'],
                    'ulnNumber' => $contentMother['uln_number'],
                    'isAlive' => true
                ]);

                if ($mother == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE MOTHER IS NOT FOUND'
                    ], 428);
                }
            }
        }

        $motherCompany = $mother->getLocation()->getCompany();
        if($company != $motherCompany) {
            return new JsonResponse([
                Constant::CODE_NAMESPACE => 428,
                Constant::MESSAGE_NAMESPACE => 'THE MOTHER IS NOT IN YOUR LIVESTOCK'
            ], 428);
        }

        /** @var Litter $litter */
        foreach($mother->getLitters() as $motherLitter) {
            $litterDate = $motherLitter->getLitterDate()->format('Y-m-d');
            $contentDate = (new \DateTime($content['date_of_birth']))->format('Y-m-d');

            if($litterDate == $contentDate) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE MOTHER ALREADY HAS A LITTER REGISTERED ON THIS DATE'
                ], 428);
            }
        }

        $litter->setAnimalMother($mother);

        // Father
        $repository = $this->getDoctrine()->getRepository(Constant::RAM_REPOSITORY);
        $contentFather = $content['father'];

        $father = null;

        if(key_exists('pedigree_country_code', $contentFather) && key_exists('pedigree_number', $contentFather)) {
            if($contentFather['pedigree_country_code'] != '' && $contentFather['pedigree_number'] != '') {
                $father = $repository->findOneBy([
                    'pedigreeCountryCode' => $contentFather['pedigree_country_code'],
                    'pedigreeNumber' => $contentFather['pedigree_number'],
                    'isAlive' => true
                ]);

                if($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE PEDIGREE OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        if(key_exists('uln_country_code', $contentFather) && key_exists('uln_number', $contentFather)) {
            if ($contentFather['uln_country_code'] != '' && $contentFather['uln_number'] != '') {
                $father = $repository->findOneBy([
                    'ulnCountryCode' => $contentFather['uln_country_code'],
                    'ulnNumber' => $contentFather['uln_number'],
                    'isAlive' => true
                ]);

                if ($father == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'THE ULN OF THE FATHER IS NOT FOUND'
                    ], 428);
                }

                $litter->setAnimalFather($father);
            }
        }

        // Persist Litter
        $this->getDoctrine()->getManager()->persist($litter);

        // Children
        $repository = $this->getDoctrine()->getRepository(Constant::TAG_REPOSITORY);
        $contentChildren = $content['children'];

        $isAliveCounter = 0;
        foreach($contentChildren as $contentChild) {

            // Child
            $contentGender = $contentChild['gender'];

            $child = new Neuter();
            if($contentGender == 'MALE') {
                $child = new Ram();

            }

            if($contentGender == 'FEMALE') {
                $child = new Ewe();
            }

            if($father != null) {
                $child->setParentFather($father);
            }

            $child->setLitter($litter);
            $child->setLocation($location);
            $child->setParentMother($mother);
            $child->setDateOfBirth(new \DateTime($content['date_of_birth']));
            $child->setBirthProgress($contentChild['birth_progress']);
            $child->setIsAlive(false);

            if($contentChild['birth_weight'] < 0 || $contentChild['birth_weight'] > 10) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE WEIGHT HAS TO BE BETWEEN 0 AND 10',
                    "data" => $contentChild['uln_country_code'] ." ". $contentChild['uln_number']
                ], 428);
            }

            if($contentChild['tail_length'] < 0 || $contentChild['tail_length'] > 10) {
                return new JsonResponse([
                    Constant::CODE_NAMESPACE => 428,
                    Constant::MESSAGE_NAMESPACE => 'THE TAIL LENGTH HAS TO BE BETWEEN 0 AND 30',
                    "data" => $contentChild['uln_country_code'] ." ". $contentChild['uln_number']
                ], 428);
            }


            if($contentChild['is_alive']) {
                $child->setIsAlive(true);

                // Tag
                $tag = $repository->findOneBy([
                    'ulnCountryCode' => $contentChild['uln_country_code'],
                    'ulnNumber' => $contentChild['uln_number'],
                    'tagStatus' => 'UNASSIGNED',
                    'owner' => $company->getOwner()
                ]);

                if($tag == null) {
                    return new JsonResponse([
                        Constant::CODE_NAMESPACE => 428,
                        Constant::MESSAGE_NAMESPACE => 'YOU DO NOT OWN THIS UNASSIGNED TAG',
                        "data" => $contentChild['uln_country_code'] ." ". $contentChild['uln_number']
                    ], 428);
                }

                $tag->setTagStatus(TagStateType::ASSIGNING);
                $child->setUlnNumber($tag->getUlnNumber());
                $child->setUlnCountryCode($tag->getUlnCountryCode());
                $child->setAnimalOrderNumber($tag->getAnimalOrderNumber());
                $this->persist($tag);

                // Surrogate
                if($contentChild['nurture_type'] == 'SURROGATE') {
                    $repository = $this->getDoctrine()->getRepository(Constant::EWE_REPOSITORY);
                    $contentSurrogate = $contentChild['surrogate'];
                    $surrogate = $repository->findOneBy([
                        'ulnCountryCode' => $contentSurrogate['uln_country_code'],
                        'ulnNumber' => $contentSurrogate['uln_number'],
                        'isAlive' => true
                    ]);

                    if($surrogate == null) {
                        return new JsonResponse([
                            Constant::CODE_NAMESPACE => 428,
                            Constant::MESSAGE_NAMESPACE => 'THE SURROGATE IS NOT IN YOUR LIVESTOCK'
                        ], 428);
                    }
                    $child->setSurrogate($surrogate);
                }

                // Lambar
                $child->setLambar(($contentChild['nurture_type'] == 'LAMBAR'));

                $animalDetails = new ArrayCollection();
                $animalDetails['animal'] = $child;
                $animalDetails['location'] = $location;
                $animalDetails['nurture_type'] = $contentChild['nurture_type'];
                $animalDetails['birth_type'] = $contentChild['birth_progress'];
                $animalDetails['birth_weight'] = $contentChild['birth_weight'];
                $animalDetails['tail_length'] = $contentChild['tail_length'];
                $animalDetails['litter_size'] = sizeof($contentChildren);

                // Persist Message
                $messageObject = $this->buildMessageObject(RequestType::DECLARE_BIRTH_ENTITY, $animalDetails, $client, $loggedInUser, $location);
                $this->sendMessageObjectToQueue($messageObject);
                $this->persist($messageObject);

                // Counter
                $isAliveCounter += 1;
            }

            if(!($child->getIsAlive())) {

                // Weight
                $weight = new Weight();
                $weight->setMeasurementDate(new \DateTime($content['date_of_birth']));
                $weight->setAnimal($child);
                $weight->setIsBirthWeight(true);
                $weight->setWeight($contentChild['birth_weight']);
                $this->getDoctrine()->getManager()->persist($weight);

                // Tail Length
                $tailLength = new TailLength();
                $tailLength->setMeasurementDate(new \DateTime($content['date_of_birth']));
                $tailLength->setAnimal($child);
                $tailLength->setLength($contentChild['tail_length']);
                $this->getDoctrine()->getManager()->persist($tailLength);

                // Persist Child & Add to litter
                $litter->addChild($child);
                $this->getDoctrine()->getManager()->persist($child);
            }
        }

        // Update & Persist Litter
        $litter->setBornAliveCount($isAliveCounter);
        $litter->setStillbornCount(sizeof($contentChildren)-$isAliveCounter);

        $this->getDoctrine()->getManager()->persist($litter);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([Constant::RESULT_NAMESPACE => 'ok'], 200);
    }

  /**
   * Update existing DeclareBirth request
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
   *   description = "Update a DeclareBirth request",
   *   input = "AppBundle\Entity\DeclareBirth",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareBirthRepository")
   * @Method("PUT")
   */
  public function updateBirth(Request $request, $Id) {

      $content = $this->getContentAsArray($request);
      $client = $this->getAuthenticatedUser($request);
      $loggedInUser = $this->getLoggedInUser($request);
      $location = $this->getSelectedLocation($request);

      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);
      $declareBirth = $entityManager->getBirthByRequestId($client, $content->get("request_id"));

      if($declareBirth == null) {
          $message = 'no message found for the given requestId';
          $messageArray = array('code'=>400, "message" => $message);

          return new JsonResponse($messageArray, 400);
      }

      //TODO Phase 2: Minimize validity check for all controllers
      $validityCheckUlnOrPedigree = $this->isUlnOrPedigreeCodeValid($request);
      $isValid = $validityCheckUlnOrPedigree['isValid'];

      if(!$isValid) {
          $keyType = $validityCheckUlnOrPedigree['keyType']; // uln  of pedigree
          $animalKind = $validityCheckUlnOrPedigree['animalKind'];
          $message = $keyType . ' of ' . $animalKind . ' not found.';
          $messageArray = array('code'=>428, "message" => $message);

          return new JsonResponse($messageArray, 428);
      }

      //Validate if tag is available
      $verification = $this->isTagUnassigned($content->get('animal')['uln_country_code'],
          $content->get('animal')['uln_number']);
      if(!$verification['isValid']) {
          return $verification['jsonResponse'];
      }

      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareBirthUpdate = $this->buildEditMessageObject(RequestType::DECLARE_BIRTH_ENTITY,
          $content, $client, $loggedInUser, $location);

      //First Persist object to Database, before sending it to the queue
      $this->persist($declareBirthUpdate);

      //Send it to the queue and persist/update any changed state to the database
      $messageArray = $this->sendEditMessageObjectToQueue($declareBirthUpdate);

    return new JsonResponse($messageArray, 200);
  }

    /**
     *
     * Get DeclareBirths & DeclareStillborns which have failed last responses.
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
     *   description = "Get DeclareBirths & DeclareStillborns which have failed last responses",
     *   input = "AppBundle\Entity\DeclareBirth",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-errors")
     * @Method("GET")
     */
    public function getBirthErrors(Request $request)
    {
        $location = $this->getSelectedLocation($request);

        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        $birthRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_RESPONSE_REPOSITORY);
        $declareBirths = $birthRepository->getBirthsWithLastErrorResponses($location, $animalRepository);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('births' => $declareBirths)), 200);
    }


    /**
     *
    /**
     *
     * For the history view, get DeclareBirths & DeclareStillborns which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED.
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
     *   description = "Get DeclareBirths & DeclareStillborns which have the following requestState: OPEN or REVOKING or REVOKED or FINISHED",
     *   input = "AppBundle\Entity\DeclareBirth",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     *
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-history")
     * @Method("GET")
     */
    public function getBirthHistory(Request $request)
    {
        $location = $this->getSelectedLocation($request);
        $animalRepository = $this->getDoctrine()->getRepository(Constant::ANIMAL_REPOSITORY);
        $birthRepository = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_RESPONSE_REPOSITORY);
        $declareBirths = $birthRepository->getBirthsWithLastHistoryResponses($location, $animalRepository);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => array('births' => $declareBirths)), 200);
    }
}