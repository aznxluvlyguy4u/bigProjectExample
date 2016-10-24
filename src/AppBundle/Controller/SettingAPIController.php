<?php

namespace AppBundle\Controller;


use AppBundle\Constant\Constant;
use AppBundle\Entity\InvoiceRuleTemplate;
use AppBundle\Enumerator\AccessLevelType;
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
    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getInvoiceRuleTemplate(Request $request)
    {
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        $em = $this->getDoctrine()->getManager();

        $sql = "SELECT
                  invoice_rule_template.id,
                  invoice_rule_template.description,
                  invoice_rule_template.vat_percentage_rate,
                  invoice_rule_template.price_excl_vat,
                  invoice_rule_template.sort_order,
                  invoice_rule_template.category
                FROM
                  invoice_rule_template";

        $results = $em->getConnection()->query($sql)->fetchAll();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $results), 200);
    }

    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("POST")
     * @return jsonResponse
     */
    public function createInvoiceRuleTemplate(Request $request)
    {
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        $content = $this->getContentAsArray($request);

        // Create Owner
        $description = $content->get('description');
        $vatPercentageRate = floatval($content->get('vat_percentage_rate'));
        $priceExclVat =  floatval($content->get('price_excl_vat'));
        $sortOrder =  intval($content->get('sort_order'));
        $category = $content->get('category');

        $ruleTemplate = new InvoiceRuleTemplate();
        $ruleTemplate->setDescription($description);
        $ruleTemplate->setVatPercentageRate($vatPercentageRate);
        $ruleTemplate->setPriceExclVat($priceExclVat);
        $ruleTemplate->setSortOrder($sortOrder);
        $ruleTemplate->setCategory($category);

        $this->getDoctrine()->getManager()->persist($ruleTemplate);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $ruleTemplate), 200);
    }


    /**
     * @Route("/invoice-rules")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function changeInvoiceRuleTemplate(Request $request)
    {
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        $content = $this->getContentAsArray($request);

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $ruleTemplate = $repository->findOneBy(array('id' => $content->get('id')));

        if(!$ruleTemplate) {
            return new JsonResponse(
                array(
                    Constant::CODE_NAMESPACE => 400,
                    Constant::MESSAGE_NAMESPACE => 'THE INVOICE RULE TEMPLATE IS NOT FOUND.'
                ),
                400
            );
        }

        $description = $content->get('description');
        $vatPercentageRate = $content->get('vat_percentage_rate');
        $priceExclVat = $content->get('price_excl_vat');
        $sortOrder = $content->get('sort_order');
        $category = $content->get('category');

        $ruleTemplate->setDescription($description);
        $ruleTemplate->setVatPercentageRate($vatPercentageRate);
        $ruleTemplate->setPriceExclVat($priceExclVat);
        $ruleTemplate->setSortOrder($sortOrder);
        $ruleTemplate->setCategory($category);

        $this->getDoctrine()->getManager()->persist($ruleTemplate);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $ruleTemplate), 200);
    }

    /**
     * @Route("/invoice-rules/{id}")
     * @param Request $request
     * @Method("DELETE")
     * @return jsonResponse
     */
    public function deleteInvoiceRuleTemplate(Request $request, $id)
    {
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        $repository = $this->getDoctrine()->getRepository(InvoiceRuleTemplate::class);
        $ruleTemplate = $repository->findOneBy(array('id' => $id));

        $this->getDoctrine()->getManager()->remove($ruleTemplate);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => 'ok'), 200);
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
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        // TODO: Implement editReasonOfLoss() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
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
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        // TODO: Implement editReasonOfDepart() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
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
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        // TODO: Implement editTreatmentOptions() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
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
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        // TODO: Implement editContactFormOptions() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

}