<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 5-4-17
 * Time: 13:45
 */

namespace AppBundle\Controller;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Invoice;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Util\Validator;
use AppBundle\Enumerator\JMSGroups;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class InvoiceAPIController
 * @package AppBundle\Controller
 * @Route("/invoices")
 */
class InvoiceAPIController extends APIController implements InvoiceAPIControllerInterface
{
    /**
     * @Method("GET")
     * @Route("")
     * @return JsonResponse
     */
    function getInvoices()
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) { return $validationResult->getJsonResponse(); }
        $repo = $this->getManager()->getRepository(Invoice::class);
        $invoices = $repo->findAll();
        return new JsonResponse(array(Constant::RESULT_NAMESPACE =>$invoices), 200);
    }

    /**
     * @param Request $request
     * @Method("GET")
     * @Route("/by")
     * @return JsonResponse
     */
    function getInvoicesBy(Request $request)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $repo = $this->getManager()->getRepository(Invoice::class);
        $criteria = $request->query->all();
    }

    /**
     * @param Request $request
     * @Method("POST")
     * @Route("")
     * @return JsonResponse
     */
    function createInvoice(Request $request)
    {
        // 
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $content = $this->getContentAsArray($request);
        $invoice = $this->getObjectFromContent($content, Invoice::class);
        $this->persistAndFlush($invoice);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $invoice), 200);
    }

    /**
     * @param Request $request
     * @Method("PUT")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function updateInvoice(Request $request, Invoice $id)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $content = $this->getContentAsArray($request);
        $temporaryInvoice = $this->getObjectFromContent($content, Invoice::class);
        $id->copyValues($temporaryInvoice);
        $this->persistAndFlush($id);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), 200);
    }

    /**
     * @param Request $request
     * @Method("DELETE")
     * @Route("/{id}")
     * @return JsonResponse
     */
    function deleteInvoice(Request $request, Invoice $id)
    {
        $validationResult = AdminValidator::validate($this->getUser(), AccessLevelType::ADMIN);
        if (!$validationResult->isValid()) {return $validationResult->getJsonResponse();}
        $id->setIsDeleted(true);
        $this->persistAndFlush($id);
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $id), 200);
    }

}