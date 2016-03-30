<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Animal;
use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Arrival;
use AppBundle\Entity\Sheep;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use JMS\Serializer\Annotation as JMS;


/**
 * @Route("/api/v1")
 */
class ArrivalAPIController extends Controller
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
   * @Method("GET")
   */
  public function postNewArrival()
  {
    //$entityValidator = $this->get('api.entity.validate');
    //$arrival = $entityValidator->validate($request, 'AppBundle\Entity\Arrival');

    //TODO move to Service class
    $serializer = $this->get('jms_serializer');

    $arrival = new Arrival();
    $arrival->setLogDate(new \DateTime("now"));
    $arrival->setArrivalDate(new \DateTime("now"));
    $arrival->setUbn("1111");
    $arrival->setUbnPreviousOwner("12344");
    $arrival->setRequestID("12313");
    $arrival->setRelationNumberKeeper("123123133");
    $arrival->setAction("C");
    $arrival->setRecoveryIndicator("N");

    $location = new Location();
    $location->setUbn("11111");

    $ram = new Ram();
    $ram->setPedigreeCountryCode("NL");
    $ram->setPedigreeNumber("1111111");
    $ram->setUlnCountryCode("NL");
    $ram->setUlnNumber("8888888");
    $ram->setName("Molly");
    $ram->setDateOfBirth(new \DateTime("now"));
    $ram->setAnimalType(1);
    $ram->setAnimalCategory(1);

    $arrival->setLocation($location);
    $arrival->setAnimal($ram);


    $declareArrivaljson = $serializer->serialize($arrival, 'json');
    //dump($declareArrivaljson);
    //die();

    //$declareArrivalObj = $serializer->deserialize($declareArrivaljson,'AppBundle\Entity\Arrival','json');
    //dump($declareArrivalObj);

    //die();

    //$arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->persist($arrival);

    return new JsonResponse($arrival);
  }
}
