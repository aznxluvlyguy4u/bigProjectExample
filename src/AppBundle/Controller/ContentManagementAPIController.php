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
        
        $admin = $this->getAuthenticatedEmployee($request);
        $adminValidator = new AdminValidator($admin, AccessLevelType::SUPER_ADMIN);
        if (!$adminValidator->getIsAccessGranted()) { //validate if user is at least an ADMIN
             return $adminValidator->createJsonErrorResponse();
        }
        $content = $this->getContentAsArray($request);
        $em = $this->getDoctrine()->getManager();
        /** @var Content $cms */
        $cms = $em->getRepository(Content::class)->getCMS();

        //Set values
        $dashboardText = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::DASHBOARD, $content);
        $contactInfo = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::CONTACT_INFO, $content);
        $cms->setDashBoardIntroductionText($dashboardText);
        $cms->setNsfoContactInformation($contactInfo);
        $em->persist($cms);
        $em->flush();

        $outputArray = $outputArray = ContentOutput::create($cms);
    
        return new JsonResponse(array(Constant::RESULT_NAMESPACE => $outputArray), 200);
    }
}