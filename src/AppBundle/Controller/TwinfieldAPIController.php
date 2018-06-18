<?php


namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\Twinfield\TwinfieldCustomerService;
use AppBundle\Service\Twinfield\TwinfieldOfficeService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TwinfieldAPIController
 * @package AppBundle\Controller
 * @Route("api/v1/twinfield")
 */
class TwinfieldAPIController extends APIController implements TwinfieldAPIControllerInterface
{

    /**
     *
     * @ApiDoc(
     *   section = "Twinfield",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve all twinfield customers"
     * )
     * @param Request $request
     * @param $office
     * @Method("GET")
     * @Route("/{office}")
     * @return JsonResponse
     */
    public function getCustomers(Request $request, $office)
    {
        return $this->get(TwinfieldCustomerService::class)->getAllCustomers($office);
    }

    /**
     *
     * @ApiDoc(
     *   section = "Twinfield",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "Retrieve all twinfield offices"
     * )
     * @param Request $request
     * @Method("GET")
     * @Route("")
     * @return JsonResponse
     */
    public function getOffices(Request $request) {
        return $this->get(TwinfieldOfficeService::class)->getAllOffices();
    }
}