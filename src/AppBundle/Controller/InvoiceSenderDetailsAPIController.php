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
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

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
        $em = $this->getManager();
        $details = $em->getRepository(InvoiceSenderDetails::class)->findAll();
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
        $content = $this->getContentAsArray($request);
        $invoiceSenderDetails = $this->getObjectFromContent($content, InvoiceSenderDetails::class);
        $this->persistAndFlush($invoiceSenderDetails);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoiceSenderDetails), 200);
    }

    /**
     * @param Request $request
     * @return mixed
     * @Method("PUT")
     * @Route("/{invoiceSenderDetails}")
     */
    public function updateInvoiceSenderDetails(Request $request, InvoiceSenderDetails $invoiceSenderDetails)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $content = $this->getContentAsArray($request);
        $temporaryInvoiceSenderDetails = $this->getObjectFromContent($content, InvoiceSenderDetails::class);
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