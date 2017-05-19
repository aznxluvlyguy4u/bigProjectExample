<?php

namespace AppBundle\Controller;

use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\InvoiceRuleLocked;
use AppBundle\Entity\InvoiceRule;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Output\InvoiceOutput;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;
use AppBundle\Entity\InvoiceRuleTemplate;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Util\Validator;
use AppBundle\Enumerator\JMSGroups;
use Doctrine\ORM\QueryBuilder;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;

/**
 * Class ClientInvoiceAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/invoices/client")
 */
class ClientInvoiceAPIController extends APIController
{
    /**
     * @param Request $request
     * @Method("GET")
     * @Route("")
     * @return JsonResponse
     */
    public function getClientInvoices(Request $request) {
        /** @var Client $client */
        $client = $this->getAuthenticatedUser($request);
        /** @var Location $location */
        $location = $this->getSelectedLocation($request);

        $invoices = $this->getManager()->getRepository(Invoice::class)
            ->findBy(array('ubn' => $location->getUbn()));
        $invoices = self::checkForPaidUnpaidInvoices($invoices);
        $invoices = InvoiceOutput::createInvoiceOutputListNoCompany($invoices);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoices), 200);
    }

    private function checkForPaidUnpaidInvoices($invoices) {
        $res = array();
        foreach ($invoices as $invoice){
            /** @var Invoice $invoice */
            if ($invoice->getStatus() == "UNPAID" || $invoice->getStatus() == "PAID" || $invoice->getStatus() == "CANCELLED"){
                $res[] = $invoice;
            }
        }
        return $res;
    }

    /**
     * @Method("GET")
     * @Route("/{id}")
     * @return JsonResponse
     */
    public function getClientInvoice($id){
        $invoice = $this->getManager()->getRepository(Invoice::class)->findOneBy(array('id' => $id));
        /** @var Invoice $invoice */
        $invoice = InvoiceOutput::createInvoiceOutputNoCompany($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), 200);
    }
}