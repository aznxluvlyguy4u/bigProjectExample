<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;
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
    $birth = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY)->findOneBy(array(Constant::REQUEST_ID_NAMESPACE=>$Id));
    return new JsonResponse($birth, 200);
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
    //No explicit filter given, thus find all
    if(!$request->query->has(Constant::STATE_NAMESPACE)) {
      $declareBirths = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY)->findAll();
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareBirths = $this->getDoctrine()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY)->findBy(array(Constant::REQUEST_STATE_NAMESPACE => $state));
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareBirths), 200);
  }

  /**
   * Create a new DeclareBirth request
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
   *   description = "Post a DeclareBirth request",
   *   input = "AppBundle\Entity\DeclareBirth",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("POST")
   */
  public function createBirth(Request $request)
  {
    $validityCheckUlnOrPedigree = $this->isUlnOrPedigreeCodeValid($request);
    $isValid = $validityCheckUlnOrPedigree['isValid'];

    if(!$isValid) {
      $keyType = $validityCheckUlnOrPedigree['keyType']; // uln  of pedigree
      $animalKind = $validityCheckUlnOrPedigree['animalKind'];
      $message = $keyType . ' of ' . $animalKind . ' not found.';
      $messageArray = array('code'=>428, "message" => $message);

      return new JsonResponse($messageArray, 428);
    }

    //Get content to array
    $content = $this->getContentAsArray($request);

    //Split up the children in the array into separate messages
    $children = $content->get("children"); 
    $contentWithoutChildren = $content;
    $contentWithoutChildren->remove("children");

    $returnMessages = new ArrayCollection();

    foreach($children as $child) {

        $contentPerChild = $contentWithoutChildren;
        $contentPerChild->set('animal', $child);

        //Convert the array into an object and add the mandatory values retrieved from the database
        $declareBirthObject = $this->buildMessageObject(RequestType::DECLARE_BIRTH_ENTITY, $contentPerChild, $this->getAuthenticatedUser($request));

        //First Persist object to Database, before sending it to the queue
        $this->persist($declareBirthObject, RequestType::DECLARE_BIRTH_ENTITY);

        //Send it to the queue and persist/update any changed state to the database
        $this->sendMessageObjectToQueue($declareBirthObject, RequestType::DECLARE_BIRTH_ENTITY, RequestType::DECLARE_BIRTH);

        $returnMessages->add($declareBirthObject);
    }

    return new JsonResponse($returnMessages, 200);
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

      $validityCheckUlnOrPedigree = $this->isUlnOrPedigreeCodeValid($request);
      $isValid = $validityCheckUlnOrPedigree['isValid'];

      if(!$isValid) {
          $keyType = $validityCheckUlnOrPedigree['keyType']; // uln  of pedigree
          $animalKind = $validityCheckUlnOrPedigree['animalKind'];
          $message = $keyType . ' of ' . $animalKind . ' not found.';
          $messageArray = array('code'=>428, "message" => $message);

          return new JsonResponse($messageArray, 428);
      }

      //Convert the array into an object and add the mandatory values retrieved from the database
      $declareBirthUpdate = $this->buildEditMessageObject(RequestType::DECLARE_BIRTH_ENTITY,
          $this->getContentAsArray($request), $this->getAuthenticatedUser($request));

      $entityManager = $this->getDoctrine()->getEntityManager()->getRepository(Constant::DECLARE_BIRTH_REPOSITORY);
      $declareBirth = $entityManager->updateDeclareBirthMessage($declareBirthUpdate, $Id);

        if($declareBirth == null) {
          return new JsonResponse(array("message"=>"No DeclareBirth found with request_id:" . $Id), 204);
        }


    if ($declareBirthUpdate->getAnimal() != null) {
      $declareBirth->setAnimal($declareBirthUpdate->getAnimal());
    }

    if ($declareBirthUpdate->getDateOfBirth() != null) {
      $declareBirth->setDateOfBirth($declareBirthUpdate->getDateOfBirth());
    }

    if ($declareBirthUpdate->getBirthType() != null) {
      $declareBirth->setBirthType($declareBirthUpdate->getBirthType());
    }

    if ($declareBirthUpdate->getLocation() != null) {
      $declareBirth->setLocation($declareBirthUpdate->getLocation());
    }

    if ($declareBirthUpdate->getLambar() != null) {
      $declareBirth->setLambar($declareBirthUpdate->getLambar());
    }

    if ($declareBirthUpdate->getAborted() != null) {
      $declareBirth->setAborted($declareBirthUpdate->getAborted());
    }

    if($declareBirthUpdate->getUbnPreviousOwner() != null) {
      $declareBirth->setUbnPreviousOwner($declareBirthUpdate->getUbnPreviousOwner());
    }

    if ($declareBirthUpdate->getPseudoPregnancy() != null) {
      $declareBirth->setPseudoPregnancy($declareBirthUpdate->getPseudoPregnancy());
    }

    if ($declareBirthUpdate->getLitterSize() != null) {
      $declareBirth->setLitterSize($declareBirthUpdate->getLitterSize());
    }

    if ($declareBirthUpdate->getAnimalWeight() != null) {
      $declareBirth->setAnimalWeight($declareBirthUpdate->getAnimalWeight());
    }

    if ($declareBirthUpdate->getTailLength() != null) {
      $declareBirth->setTailLength($declareBirthUpdate->getTailLength());
    }

    if ($declareBirthUpdate->getTransportationCode() != null) {
      $declareBirth->setTransportationCode($declareBirthUpdate->getTransportationCode());
    }

    $declareBirth = $entityManager->update($declareBirth);

    return new JsonResponse($declareBirth, 200);
  }
}