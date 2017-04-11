<?php

namespace AppBundle\Controller;

use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Util\RequestUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * @Route("/api/v1/pedigreeregisters")
 */
class PedigreeRegisterAPIController extends APIController implements PedigreeRegisterAPIControllerInterface {

  /**
   * Get PedigreeRegisters.
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
   *   description = "Get PedigreeRegisters",
   *   output = "AppBundle\Entity\PedigreeRegister"
   * )
   * @param Request $request the request object
   * @return JsonResponse
   * @Route("")
   * @Method("GET")
   */
  public function getPedigreeRegisters(Request $request)
  {
    $includeNonNsfoRegisters = RequestUtil::getBooleanQuery($request, JsonInputConstant::INCLUDE_NON_NSFO_REGISTERS);
    /** @var PedigreeRegisterRepository $repository */
    $repository = $this->getDoctrine()->getRepository(PedigreeRegister::class);

    if($includeNonNsfoRegisters) {
      $pedigreeRegisters = $repository->findAll();
    } else {
      $pedigreeRegisters = $repository->getNsfoRegisters();
    }

    $output = $this->getDecodedJson($pedigreeRegisters);
    return new JsonResponse(array(Constant::RESULT_NAMESPACE => $output), 200);
  }


}