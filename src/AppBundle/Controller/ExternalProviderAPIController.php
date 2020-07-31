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
     * @Route("/offices/{office}/customers/{customer}/invoices")
     * @return JsonResponse
     * @throws \Exception
     */
    public function getInvoicesOfCustomer($office, $customer)
    {
        return $this->get(ExternalProviderInvoiceService::class)->getAllInvoicesForCustomer($office, $customer);
    }
}
