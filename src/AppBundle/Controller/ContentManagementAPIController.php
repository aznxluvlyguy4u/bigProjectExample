<?php

namespace AppBundle\Controller;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentRepository;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\ContentOutput;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Validation\AdminValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;

/**
 * Class ContentManagementAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/cms")
 */
class ContentManagementAPIController extends APIController implements ContentManagementAPIControllerInterface
{
    /**
     *
     * Get Content from intro text and contact info.
     *
     * @Route("")
     * @param Request $request
     * @Method("GET")
     * @return jsonResponse
     */
    public function getContentManagement(Request $request)
    {
        /** @var ContentRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(Content::class);
        $cms = $repository->getCMS();
        
        $outputArray = ContentOutput::create($cms);

        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }


    /**
     *
     * Update Content
     *
     * Example of a request.
     * {
     *    "dashboard": "Welcome to our awesome system!",
     *    "contact_info": "Postbus ... E-mail-adres: kantoor@nsfo.nl"
     * }
     *
     * @Route("")
     * @param Request $request
     * @Method("PUT")
     * @return jsonResponse
     */
    public function editContentManagement(Request $request)
    {
        
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::SUPER_ADMIN)) { //validate if user is at least an ADMIN
             return AdminValidator::getStandardErrorResponse();
        }
        $content = $this->getContentAsArray($request);

        /** @var Content $cms */
        $cms = $this->getManager()->getRepository(Content::class)->getCMS();

        //Set values
        $dashboardText = $content->get(JsonInputConstant::DASHBOARD);
        $contactInfo = $content->get(JsonInputConstant::CONTACT_INFO);

        $updatedDashboardText = false;
        if ($cms->getDashBoardIntroductionText() !== $dashboardText) {
            $cms->setDashBoardIntroductionText($dashboardText);
            $updatedDashboardText = true;
        }

        $updatedContactInfo = false;
        if ($cms->getNsfoContactInformation() !== $contactInfo) {
            $cms->setNsfoContactInformation($contactInfo);
            $updatedContactInfo = true;
        }

        if ($updatedDashboardText || $updatedContactInfo) {
            $this->getManager()->persist($cms);
            $this->getManager()->flush();
        }


        if ($updatedDashboardText) { AdminActionLogWriter::updateDashBoardIntro($this->getManager(), $admin, $dashboardText); }
        if ($updatedContactInfo) { AdminActionLogWriter::updateContactInfo($this->getManager(), $admin, $contactInfo); }

        $outputArray = $outputArray = ContentOutput::create($cms);
    
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }
}