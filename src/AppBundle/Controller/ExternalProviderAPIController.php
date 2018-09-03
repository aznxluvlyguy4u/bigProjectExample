<?php


namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\ExternalProvider\ExternalProviderCustomerService;
use AppBundle\Service\ExternalProvider\ExternalProviderOfficeService;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

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
     * @param $office
     * @Method("GET")
     * @Route("/offices/{office}/customers")
     * @return JsonResponse
     */
    public function getCustomers($office)
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
     * @Method("GET")
     * @Route("/offices")
     * @return JsonResponse
     */
    public function getOffices() {
        return $this->get(ExternalProviderOfficeService::class)->getAllOfficesResponse();
    }
}