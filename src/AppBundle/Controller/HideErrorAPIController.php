<?php

namespace AppBundle\Controller;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class HideErrorAPIController
 * @package AppBundle\Controller
 * @Route("/api/v1/errors")
 */
class HideErrorAPIController extends APIController implements HideErrorAPIControllerInterface
{

    /**
     * Hide an error a user does not want to see anymore,
     * by updating the existing DeclareBase's isRemovedByUser to true.
     *
     * @ApiDoc(
     *   section = "Errors",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "hide an error response for any IR-declaration"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("PUT")
     */
    public function updateError(Request $request) {
        $content = RequestUtil::getContentAsArray($request);
        $requestId = $content->get("request_id");
        $isRemovedByUserBoolean = $content['is_removed_by_user'];

        if($requestId != null) {

            $sql = "UPDATE declare_base SET hide_failed_message = ".StringUtil::getBooleanAsString($isRemovedByUserBoolean)."
            WHERE request_id = '".$requestId."'";
            $this->getDoctrine()->getManager()->getConnection()->exec($sql);

            return new JsonResponse(array("code"=>200, "message"=>"saved"), 200);
        }

        return new JsonResponse(array('code' => 428, "message" => "fill in message number"), 428);
    }


    /**
     * Hide an error a user does not want to see anymore,
     * by updating the existing DeclareNsfoBase's isRemovedByUser to true.
     *
     * @ApiDoc(
     *   section = "Errors",
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "hide an error response for any non-IR-declaration"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("-nsfo/{messageId}")
     * @Method("PUT")
     */
    public function updateNsfoDeclarationError(Request $request, $messageId) {
        $content = RequestUtil::getContentAsArray($request);
        $isHidden = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::IS_HIDDEN, $content);

        if($messageId !== null && $isHidden !== null) {

            /** @var DeclareNsfoBase $nsfoMessage */
            $nsfoMessage = $this->getDoctrine()->getRepository(DeclareNsfoBase::class)->findOneByMessageId($messageId);

            $nsfoMessage->setIsHidden($isHidden);
            $this->persistAndFlush($nsfoMessage);

            return new JsonResponse(["code"=>200, "message"=>"saved"], 200);
        }

        return new JsonResponse(['code' => 428, "message" => "fill in messageId and hidden boolean"], 428);
    }
}