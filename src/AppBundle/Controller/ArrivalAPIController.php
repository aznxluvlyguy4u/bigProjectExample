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
   * @Route("/arrival/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\ArrivalRepository")
   * @Method("GET")
   */
  public function getArrivaldAction($Id)
  {
    $arrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->find($Id);
    return new JsonResponse($arrival);
  }

  /**
   * @Route("/arrival/new")
   * @Method("GET")
   */
  public function getArrivalAction()
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

    //$declareArrival = $this->getDoctrine()->getRepository('AppBundle:Arrival')->find;

    return new JsonResponse($declareArrival);
  }

  /**
   * @Route("/arrival")
   * @Method("POST")
   */
  public function postArrivalAction(Request $request)
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
