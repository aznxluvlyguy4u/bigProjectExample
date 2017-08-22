<?php

namespace AppBundle\Controller;


use AppBundle\Constant\Constant;
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
        $admin = $this->getEmployee();
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
        $admin = $this->getEmployee();
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
        $admin = $this->getEmployee();
        $adminValidator = new AdminValidator($admin, AccessLevelType::ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
            return $adminValidator->createJsonErrorResponse();
        }

        // TODO: Implement editContactFormOptions() method.
        $outputArray = array();

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }

}