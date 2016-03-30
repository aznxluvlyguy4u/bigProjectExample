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

/**
 * @Route("/api/v1")
 */
class ArrivalAPIController extends APIController
{

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
    //Validate requestBody
    $entityValidator = $this->get('api.entity.validate');

    //Parse to ArrivalEntity
    //$arrival = $entityValidator->validate($request, 'AppBundle\Entity\Arrival');
    $declareArrival = $this->deserializeToObject($request->getContent(),'AppBundle\Entity\Arrival');

    $declareArrivalJSON = $this->serializeToJSON($declareArrival);

    //Send serialized message to Queue
    $result = $this->getQueueService()->send($declareArrivalJSON);

    //TODO - Add logic for success/failure sending to Q, add request state to object

    //Persist object to Database
    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->persist($declareArrival);

    return new JsonResponse($arrival);
  }
}
