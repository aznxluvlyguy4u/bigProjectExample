<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Enumerator\RequestType;

/**
 * @Route("/api/v1/imports")
 */
class ImportAPIController extends APIController implements ImportAPIControllerInterface {

  /**
   * Retrieve a DeclareImport, found by it's ID.
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
   *   description = "Retrieve a DeclareImport by given ID",
   *   output = "AppBundle\Entity\DeclareArrival"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareImport to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareImportRepository")
   * @Method("GET")
   */
  public function getImportById(Request $request, $Id) {
    //TODO for phase 2: read a location from the $request and find declareImports for that location
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);

    $import = $repository->getImportsById($client, $Id);

    return new JsonResponse($import, 200);
  }

  /**
   * Retrieve either a list of all DeclareImports or a subset of DeclareImports with a given state-type:
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
   *        "description"=" DeclareImports to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareImports",
   *   output = "AppBundle\Entity\DeclareImport"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getImports(Request $request) {
    //TODO for phase 2: read a location from the $request and find declareImports for that location
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_IMPORT_REPOSITORY);

    if(!$stateExists) {
      $declareImports = $repository->getImports($client);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareImports = new ArrayCollection();
      //TODO Front-end cannot accept messages without animal ULN/Pedigree
//      foreach($repository->getImports($client, RequestStateType::OPEN) as $import) {
//        $declareImports->add($import);
//      }
      foreach($repository->getImports($client, RequestStateType::REVOKING) as $import) {
        $declareImports->add($import);
      }
      foreach($repository->getImports($client, RequestStateType::FINISHED) as $import) {
        $declareImports->add($import);
      }
      
    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareImports = $repository->getImports($client, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareImports), 200);
  }


}