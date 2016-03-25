<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Entity\Animal;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/api/v1")
 */
class AnimalAPIController extends Controller
{

  /**
   * @Route("/arrival/{arrival}")
   * @Method("GET")
   */
  public function getIndexAction()
  {
    $animals = $this->getDoctrine()->getRepository('AppBundle:Animal')->findAll();

    return new JsonResponse($animals);
  }

  /**
   * @Route("/animal/{id}")
   * @ParamConverter("animal", class="AppBundle:Animal")
   * @Method("GET")
   */
  public function getAnimalAction($animal)
  {
    return new JsonResponse($animal);
  }

  /**
   * @Route("/animal")
   * @Method("POST")
   */
  public function postAnimalAction(Request $request)
  {
    $owners = $request->request->get('owners');
    $form = $request->request->get('form');
    $locations = $request->request->get('locations');
    $startdate = $request->request->get('startdate');
    $enddate = $request->request->get('enddate');

    $ev = $this->get('api.entity.validate');
    $animal = $ev->validate($request, 'AppBundle\Entity\Animal');

    if (!$animal) {
      return new JsonResponse($ev->getErrors(), 400);
    }

    $em = $this->getDoctrine()->getRepository('AppBundle:Animal')->persist($animal);

    return new JsonResponse($animal);
  }
}
