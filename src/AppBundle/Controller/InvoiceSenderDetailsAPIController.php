<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 7-4-17
 * Time: 10:24
 */

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Entity\BillingAddress;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class InvoiceSenderDetailsAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/invoice_sender_details")
 */
class InvoiceSenderDetailsAPIController extends APIController implements InvoiceSenderDetailsAPIControllerInterface
{
    /**
     * @return JsonResponse
     * @Route("")
     * @Method("GET")
     */
    public function getInvoiceSenderDetails()
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $serializer = $this->getSerializer();
        $em = $this->getManager();
        $details = $em->getRepository(InvoiceSenderDetails::class)->createQueryBuilder('qb')
            ->orderBy('qb.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $details), 200);
    }

    /**
     * @param Request $request
     * @return mixed
     * @Method("POST")
     * @Route("")
     */
    public function createInvoiceSenderDetails(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $details = new InvoiceSenderDetails();
        $content = $this->getContentAsArray($request);
        $contentAddress = $content->get('address');
        $address = new BillingAddress();
        $address->setStreetName($contentAddress['street_name']);
        $address->setAddressNumber($contentAddress['address_number']);

        if(isset($contentAddress['address_number_suffix'])) {
            $address->setAddressNumberSuffix($contentAddress['address_number_suffix']);
        }

        $address->setPostalCode($contentAddress['postal_code']);
        $address->setCity("");
        $address->setCountry("Nederland");
        $details->setName($content->get('name'));
        $details->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $details->setVatNumber($content->get('vat_number'));
        $details->setPaymentDeadlineInDays($content->get('payment_deadline_in_days'));
        $details->setIban($content->get('iban'));
        $details->setAddress($address);
        $this->persistAndFlush($details);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $details), 200);
    }

    /**
     * @param Request $request, InvoiceSenderDetails $invoiceSenderDetails
     * @return mixed
     * @Method("PUT")
     * @Route("/{invoiceSenderDetails}")
     */
    public function updateInvoiceSenderDetails(Request $request, InvoiceSenderDetails $invoiceSenderDetails)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $content = $this->getContentAsArray($request);
        $temporaryInvoiceSenderDetails = new InvoiceSenderDetails();
        $contentAddress = $content->get('address');
        $temporaryAddress = new BillingAddress();
        $temporaryAddress->setStreetName($contentAddress['street_name']);
        $temporaryAddress->setAddressNumber($contentAddress['address_number']);

        if(isset($contentAddress['suffix'])) {
            $temporaryAddress->setAddressNumberSuffix($contentAddress['address_number_suffix']);
        }

        $temporaryAddress->setPostalCode($contentAddress['postal_code']);
        $temporaryAddress->setCity($contentAddress['city']);
        $temporaryAddress->setCountry($contentAddress['country']);
        $temporaryInvoiceSenderDetails->setName($content->get('name'));
        $temporaryInvoiceSenderDetails->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $temporaryInvoiceSenderDetails->setVatNumber($content->get('vat_number'));
        $temporaryInvoiceSenderDetails->setPaymentDeadlineInDays($content->get('payment_deadline_in_days'));
        $temporaryInvoiceSenderDetails->setIban($content->get('iban'));
        $temporaryInvoiceSenderDetails->setAddress($temporaryAddress);
        $invoiceSenderDetails->copyValues($temporaryInvoiceSenderDetails);
        $this->persistAndFlush($invoiceSenderDetails);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoiceSenderDetails), 200);
    }

    /**
     * @param InvoiceSenderDetails $invoiceSenderDetails
     * @return mixed
     * @Method("DELETE")
     * @Route("{invoiceSenderDetails}")
     */
    public function deleteInvoiceSenderDetails(InvoiceSenderDetails $invoiceSenderDetails)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $invoiceSenderDetails->setIsDeleted(true);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoiceSenderDetails), 200);
    }

}