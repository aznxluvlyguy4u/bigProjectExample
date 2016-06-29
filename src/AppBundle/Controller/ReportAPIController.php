<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class ReportAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/reports")
 */
class ReportAPIController extends APIController {
  

  /**
   *
   * Debug endpoint
   *
   * @Route("/debug")
   * @Method("GET")
   */
  public function debugAPI(Request $request) {
    return new JsonResponse("ok", 200);
  }


  /**
   * Get the pedigree certificates of the given animals.
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
   *   description = "Get the pedigree certificates of the given animals",
   *   output = "AppBundle\Entity\Animal"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("/pedigree-certificates")
   * @Method("POST")
   */
  public function pedigreeCertificate(Request $request) {

    $content = $this->getContentAsArray($request);
    $client  = $this->getAuthenticatedUser($request);

    //Validate if animals belong to the client
    $animals = $content->get(Constant::ANIMALS_NAMESPACE);
    foreach($animals as $animal) {
      $isAnimalOfClient = $this->getDoctrine()->getRepository(Animal::class)->verifyIfClientOwnsAnimal($client, $animal);
      $uln = $animal[Constant::ULN_COUNTRY_CODE_NAMESPACE] . $animal[Constant::ULN_NUMBER_NAMESPACE];
      //Check if uln is valid
      if(!$isAnimalOfClient) {
        return new JsonResponse(array('code'=>428, "message" => "Animal ".$uln." doesn't belong to this account."), 428);
      }
    }

    

    return new JsonResponse("ok", 200);
  }
}