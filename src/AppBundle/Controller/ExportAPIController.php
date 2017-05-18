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
   *   section = "Exports",
   *   requirements={
   *     {
   *       "name"="AccessToken",
   *       "dataType"="string",
   *       "requirement"="",
   *       "description"="A valid accesstoken belonging to the user that is registered with the API"
   *     }
   *   },
   *   resource = true,
   *   description = "Retrieve a DeclareExport by given ID"
   * )
   * @param Request $request the request object
   * @param int $Id Id of the DeclareExport to be returned
   * @return JsonResponse
   * @Route("/{Id}")
   * @ParamConverter("Id", class="AppBundle\Entity\DeclareExportRepository")
   * @Method("GET")
   */
  public function getExportById(Request $request, $Id) {
    $location = $this->getSelectedLocation($request);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

    $export = $repository->getExportByRequestId($location, $Id);

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
   *   section = "Exports",
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
   *        "description"=" DeclareExports to filter on",
   *        "format"="?state=state-type"
   *      }
   *   },
   *   resource = true,
   *   description = "Retrieve a a list of DeclareExports"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getExports(Request $request) {
    $location = $this->getSelectedLocation($request);
    $stateExists = $request->query->has(Constant::STATE_NAMESPACE);
    $repository = $this->getDoctrine()->getRepository(Constant::DECLARE_EXPORT_REPOSITORY);

    if(!$stateExists) {
      $declareExports = $repository->getExports($location);

    } else if ($request->query->get(Constant::STATE_NAMESPACE) == Constant::HISTORY_NAMESPACE ) {

      $declareExports = new ArrayCollection();
      foreach($repository->getExports($location, RequestStateType::OPEN) as $export) {
        $declareExports->add($export);
      }

      foreach($repository->getExports($location, RequestStateType::REVOKING) as $export) {
        $declareExports->add($export);
      }
      foreach($repository->getExports($location, RequestStateType::FINISHED) as $export) {
        $declareExports->add($export);
      }

    } else { //A state parameter was given, use custom filter to find subset
      $state = $request->query->get(Constant::STATE_NAMESPACE);
      $declareExports = $repository->getExports($location, $state);
    }

    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $declareExports), 200);
  }


}