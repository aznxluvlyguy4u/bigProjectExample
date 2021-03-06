<?php


namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\ExternalProvider\ExternalProviderArticleService;
use AppBundle\Service\ExternalProvider\ExternalProviderCustomerService;
use AppBundle\Service\ExternalProvider\ExternalProviderInvoiceService;
use AppBundle\Service\ExternalProvider\ExternalProviderOfficeService;
use Exception;
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
     * @param $office
     * @Method("GET")
     * @Route("/offices/{office}/customers")
     * @return JsonResponse
     * @throws Exception
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
     *   description = "Retrieve all twinfield customers"
     * )
     * @param Request $request
     * @Method("POST")
     * @Route("/customers/create")
     * @return JsonResponse
     *
     * Remove this function if you are done testing.
     * The service function (createOrEditCustomer) needs to be used in the customer controller
     */
    public function createCustomer(Request $request)
    {
        return $this->get(ExternalProviderCustomerService::class)->createOrEditCustomer($request);
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
     * @throws Exception
     */
    public function getOffices() {
        return $this->get(ExternalProviderOfficeService::class)->getAllOffices();
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
     *   description = "Retrieve all twinfield articles"
     * )
     * @Method("GET")
     * @Route("/articles")
     * @return JsonResponse
     * @throws Exception
     */
    public function getArticles() {
        return $this->get(ExternalProviderArticleService::class)->getAllArticles();
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
     *   description = "Retrieve all twinfield invoices of a specific customer"
     * )
     * @Method("GET")
     * @Route("/customer-invoices")
     * @param Request $request
     * @return JsonResponse
     */
    public function getInvoicesOfCustomer(Request $request)
    {
        return $this->get(ExternalProviderInvoiceService::class)->getAllInvoicesForCustomer($request);
    }
}
