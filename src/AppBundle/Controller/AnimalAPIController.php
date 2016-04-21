<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Ewe;
use AppBundle\Entity\Ram;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Entity\Animal;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/animals")
 */
class AnimalAPIController extends Controller
{

  /**
   * Retrieve a list of animals.
   *
   * Animal-types = { Ram, Ewe }
   *
   *
   *
   * @ApiDoc(
   *   parameters={
   *      {
   *        "name"="type",
   *        "dataType"="string",
   *        "required"=false,
   *        "description"=" animal type to retrieve",
   *        "format"="?type=animal-type"
   *      },
   *   },
   *   resource = true,
   *   description = "Retrieve a list of animals",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @param string $state
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getAllAnimalsByType()
  {
    $animals = $this->getDoctrine()->getRepository('AppBundle:Animal')->findAll();

    return new JsonResponse($animals, 200);
  }

  /**
   * Retrieve an animal, found by it's Id.
   *
   * @ApiDoc(
   *   resource = true,
   *   description = "Retrieve an Animal by given ID",
   *   output = "AppBundle\Entity\Animal"
   * )
   *
   * @param int $Id Id of the Animal to be returned
   *
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle:Animal")
   * @Method("GET")
   */
  public function getAnimalById($animal)
  {
    return new JsonResponse($animal, 200);
  }

  /**
   * Save a new animal.
   *
   * @ApiDoc(
   *   resource = true,
   *   description = "Save a new animal",
   *   input = "AppBundle\Entity\Animal",
   *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
   * )
   * @param Request $request the request object
   * @return JsonResponse

   *
   * @Route("")
   * @Method("POST")
   */
  public function postNewAnimal(Request $request)
  {
    return new JsonResponse("OK", 200);
  }

  /**
   *
   * Debug endpoint
   *
   * @Route("/test/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request)
  {
    $father = new Ram();
    $father->setUlnCountryCode("NL");
    $father->setUlnNumber("00001");
    $father->setAnimalType(1);

    $mother = new Ewe();
    $mother->setUlnCountryCode("NL");
    $mother->setUlnNumber("00002");
    $mother->setAnimalType(2);

    $child = new Ram();
    $child->setUlnCountryCode("NL");
    $child->setUlnNumber("1234566");
    $child->setAnimalType(1);
    $child->setDateOfBirth(new \DateTime());
    $child->setParentFather($father);
    $child->setParentMother($mother);

    $child = $this->getDoctrine()->getRepository('AppBundle:Ram')->persist($child);

    return new JsonResponse($child, 200);
  }
}
