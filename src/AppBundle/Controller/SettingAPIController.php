<?php

namespace AppBundle\Controller;


use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * Class SettingAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/settings")
 */
class SettingAPIController extends APIController implements SettingAPIControllerInterface
{
    const INVOICE_JMS_GROUP = 'INVOICE';

    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRuleTemplate(Request $request)
    {
        return $this->get('app.invoice.rule.template')->getInvoiceRuleTemplate($request);
    }

    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRuleTemplate(Request $request)
    {
        return $this->get('app.invoice.rule.template')->createInvoiceRuleTemplate($request);
    }


    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function changeInvoiceRuleTemplate(Request $request)
    {
        return $this->get('app.invoice.rule.template')->changeInvoiceRuleTemplate($request);
    }

    /**
     * @Route("/invoice-rules/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, $id)
    {
        return $this->get('app.invoice.rule.template')->deleteInvoiceRuleTemplate($request, $id);
    }







    /**
     * Edit reasons of loss drop down options.
     *
     * @Route("/reasons-of-loss")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editReasonsOfLoss(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // TODO: Implement editReasonOfLoss() method.
        $outputArray = array();

        return ResultUtil::successResult($outputArray);
    }

    /**
     * Edit reasons of depart drop down options.
     *
     * @Route("/reasons-of-depart")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editReasonsOfDepart(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // TODO: Implement editReasonOfDepart() method.
        $outputArray = array();

        return ResultUtil::successResult($outputArray);
    }

    /**
     * Edit treatments drop down options.
     *
     * @Route("/treatment-options")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editTreatmentOptions(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // TODO: Implement editTreatmentOptions() method.
        $outputArray = array();

        return ResultUtil::successResult($outputArray);
    }

    /**
     * Edit contactform options.
     *
     * @Route("/contactform-options")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editContactFormOptions(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        // TODO: Implement editContactFormOptions() method.
        $outputArray = array();

        return ResultUtil::successResult($outputArray);
    }

}