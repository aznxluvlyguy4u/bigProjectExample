<?php


namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\Twinfield\ExternalProviderCustomerService;
use AppBundle\Service\Twinfield\ExternalProviderOfficeService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ExternalProviderAPIController
 * @package AppBundle\Controller
 * @Route("api/v1/external-provider")
 */
class ExternalProviderAPIController extends APIController implements ExternalProviderAPIControllerInterface
{

    /**
     *
     * @ApiDoc(
     *   section = "ExternalProvider",
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
     * @Route("/offices/{office}/customers")
     * @return JsonResponse
     */
    public function getCustomers(Request $request, $office)
    {
        return $this->get(ExternalProviderCustomerService::class)->getAllCustomers($office);
    }

    /**
     *
     * @ApiDoc(
     *   section = "ExternalProvider",
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
     * @Route("/offices")
     * @return JsonResponse
     */
    public function getOffices(Request $request) {
        return $this->get(ExternalProviderOfficeService::class)->getAllOfficesResponse();
    }
}