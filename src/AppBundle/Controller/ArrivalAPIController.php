<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Location;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Arrival;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

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

    $queueService =  $this->get('app.arrivals')->getQueueService();

    $queueService->sendMessage('dd');

    //$declareArrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->find;

    return new JsonResponse($declareArrival);
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
    $entityValidator = $this->get('api.entity.validate');
    $arrival = $entityValidator->validate($request, 'AppBundle\Entity\Arrival');

    if (!$arrival) {
      return new JsonResponse($entityValidator->getErrors(), 400);
    }

    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->persist($arrival);

    return new JsonResponse($arrival);
  }
}
