<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Controller\InvoiceSenderDetailsAPIControllerInterface;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\InvoiceSenderDetails;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class InvoiceSenderDetailsService extends ControllerServiceBase implements InvoiceSenderDetailsAPIControllerInterface
{
    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getInvoiceSenderDetails()
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $details = $this->getManager()->getRepository(InvoiceSenderDetails::class)->createQueryBuilder('qb')
            ->orderBy('qb.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return ResultUtil::successResult($details);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createInvoiceSenderDetails(Request $request)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $details = new InvoiceSenderDetails();
        $content = RequestUtil::getContentAsArray($request);
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
        return ResultUtil::successResult($details);
    }


    /**
     * @param Request $request
     * @param InvoiceSenderDetails $invoiceSenderDetails
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateInvoiceSenderDetails(Request $request, InvoiceSenderDetails $invoiceSenderDetails)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }

        $content = RequestUtil::getContentAsArray($request);
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
        return ResultUtil::successResult($invoiceSenderDetails);
    }


    /**
     * @param InvoiceSenderDetails $invoiceSenderDetails
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteInvoiceSenderDetails(InvoiceSenderDetails $invoiceSenderDetails)
    {
        if (!AdminValidator::isAdmin($this->getUser(), AccessLevelType::ADMIN))
        { return AdminValidator::getStandardErrorResponse(); }
        $invoiceSenderDetails->setIsDeleted(true);
        return ResultUtil::successResult($invoiceSenderDetails);
    }
}