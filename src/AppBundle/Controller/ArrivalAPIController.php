<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Arrival;
use AppBundle\Entity\Sheep;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Route("/api/v1")
 */
class ArrivalAPIController extends APIController
{
  const REQUEST_TYPE = 'DECLARE_ARRIVAL';

  /**
   *
   * Get a DeclareArrival, found by it's ID.
   *
   * @Route("/arrival/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\ArrivalRepository")
   * @Method("GET")
   */
  public function getArrivalById($Id)
  {
    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->find($Id);
    return new JsonResponse($arrival);
  }

  /**
   *
   * Get a list of DeclareArrivals with a given state:{OPEN, CLOSED, DECLINED}.
   *
   *
   * @Route("/arrival/new/open")
   * @Method("GET")
   */
  public function getArrivalByState()
  {
    $entityManager = $this->getDoctrine()->getManager();

    $user = $this->getDoctrine()->getRepository('AppBundle:Arrival')->findBy(['id' => 1]);

    //Create location
    $location = new Location();
    $location->setUbn("11111111");

    $entityManager->persist($location);

    //Create Animal
    $animal = new Ram();
    $animal->setDateOfBirth(new \DateTime('tomorrow'));
    $animal->setAnimalCategory(1);
    $animal->setAnimalType(3);
    $animal->setGender("male");
    $animal->setName("brian");
    $animal->setPedigreeCountryCode("NL");
    $animal->setPedigreeNumber("111111111");
    $animal->setUlnCountryCode("NL");
    $animal->setUlnNumber("1123333");

    //Create DeclareArrival
    $declareArrival = new Arrival();
    $declareArrival->setAction("C");
    $declareArrival->setAnimal($animal);
    $declareArrival->setArrivalDate(new \DateTime('tomorrow'));
    $declareArrival->setLocation($location);
    $declareArrival->setLogDate(new \DateTime('tomorrow'));

    $entityManager->persist($declareArrival);

    $queueService =  $this->get('app.aws.queueservice');
    $queueService->getQueueService();

    //TODO move to Service class
    $encoders = array(new JsonEncoder());
    $normalizers = array(new ObjectNormalizer());

    $serializer = new Serializer($normalizers, $encoders);

    $declareArrivaljson = $serializer->serialize($declareArrival, 'json');

    //TODO
    return new JsonResponse($queueService->send($declareArrivaljson));
  }

  /**
   *
   * Create a DeclareArrival Request.
   *
   * @Route("/arrival")
   * @Method("POST")
   */
  public function postNewArrival(Request $request)
  {
    //Get content to array
    $content = $this->getContentAsArray($request);

    /**
     * Create additional request properties.
     *
     * Strategy: get below User details based on token passed,
     * filter database to get user belonging to the given token.
     */
    //$user = $this->getUserByToken($request)[0];
    //$ubn = ($user->getLocations()->get(0)->getUbn());
    //$relationNumberKeeper = $user->getRelationNumberKeeper();

    //Generate new requestId
    $requestId = $this->getNewRequestId();

    $content->set('request_state', 'open');
    $content->set('request_id', $requestId);
    $content->set('message_id', $requestId);
    $content->set('log_date', new \DateTime("now"));
    $content->set('relation_number_keeper', '191919191');
    $content->set('action', "C");
    $content->set('recovery_indicator', "N");
    $content->set('location', array('ubn' => '11111111'));

    $animal = $content['animal'];

    $newAnimalDetails = array_merge($animal,
      array('type' => 'ram',
            'animal_type' => '1',
      ));

    $content->set('animal', $newAnimalDetails);

    //Serialize after added properties to JSON
    $declareArrivalJSON = $this->serializeToJSON($content);

    //Deserialize to Arrival
    $declareArrival = $this->deserializeToObject($declareArrivalJSON,'AppBundle\Entity\Arrival');

    //Send serialized message to Queue
    $result = $this->getQueueService()->send($requestId, $declareArrivalJSON, $this::REQUEST_TYPE);

    //TODO - Add logic for success/failure sending to Q, add request state to object

    //Persist object to Database
    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->persist($declareArrival);

    return new JsonResponse($declareArrival);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/arrival/test/foo")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {

    $user = $this->getUserByToken($request)[0];

    dump($user);
    die();

    $ubn = ($user->getLocations()->get(0)->getUbn());



    return new JsonResponse($user);
  }

}
