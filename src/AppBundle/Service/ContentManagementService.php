<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Content;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\ContentOutput;
use AppBundle\Util\AdminActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;

class ContentManagementService extends ControllerServiceBase
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getContentManagement(Request $request)
    {
        $cms = $this->getManager()->getRepository(Content::class)->getCMS();
        $outputArray = ContentOutput::create($cms);
        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function editContentManagement(Request $request)
    {
        $admin = $this->getEmployee();
        if (!AdminValidator::isAdmin($admin, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $content = RequestUtil::getContentAsArrayCollection($request);

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
        return ResultUtil::successResult($outputArray);
    }
}
