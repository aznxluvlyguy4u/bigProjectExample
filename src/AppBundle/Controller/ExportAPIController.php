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
 * @Route("/api/v1/exports")
 */
class ExportAPIController extends APIController implements ExportAPIControllerInterface {

  /**
   * Retrieve a DeclareExport, found by it's ID.
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
   *   description = "Retrieve a DeclareExport by given ID",
   *   output = "AppBundle\Entity\DeclareExport"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareExport to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareExportRepository")
   * @Method("GET")
   */
  public function getExportById(Request $request, $Id) {
    //TODO for phase 2: read a location from the $request and find declareExports for that location
    $client = $this->getAuthenticatedUser($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

    $export = $repository->getExportsById($client, $Id);

    return new JsonResponse($export, 200);
  }

  /**
   * Retrieve either a list of all DeclareExports or a subset of DeclareExports with a given state-type:
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
   *        "description"=" DeclareExportss to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareExports",
   *   output = "AppBundle\Entity\DeclareExport"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getExports(Request $request) {
    //TODO for phase 2: read a location from the $request and find declareExports for that location
    $client = $this->getAuthenticatedUser($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

    if(!$stateExists) {
      $declareExports = $repository->getExports($client);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareExports = new ArrayCollection();
      //TODO Front-end cannot accept messages without animal ULN/Pedigree
//      foreach($repository->getExports($client, RequestStateType::OPEN) as $export) {
//        $declareExports->add($export);
//      }
      foreach($repository->getExports($client, RequestStateType::REVOKING) as $export) {
        $declareExports->add($export);
      }
      foreach($repository->getExports($client, RequestStateType::FINISHED) as $export) {
        $declareExports->add($export);
      }

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareExports = $repository->getExports($client, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareExports), 200);
  }


}