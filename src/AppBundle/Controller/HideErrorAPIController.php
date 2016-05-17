<?php

namespace AppBundle\Controller;


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
     * by updating the existing DeclareArrivalResponse's isRemovedByUser to true.
     * TODO verify this javadoc
     * @ApiDoc(
     *   requirements={
     *     {
     *       "name"="AccessToken",
     *       "dataType"="string",
     *       "requirement"="",
     *       "description"="A valid accesstoken belonging to the user that is registered with the API"
     *     }
     *   },
     *   resource = true,
     *   description = "hide an error response for any declaration",
     *   input = "AppBundle\Component\HttpFoundation\JsonResponse",
     *   output = "AppBundle\Component\HttpFoundation\JsonResponse"
     * )
     * @param Request $request the request object
     * @return JsonResponse
     * @Route("")
     * @Method("PUT")
     */
    public function updateError(Request $request) {
        $content = $this->getContentAsArray($request);
        $messageNumber = $content->get("message_number");

        if($messageNumber != null) {

            $response = $this->getDoctrine()->getRepository(Constant::DECLARE_BASE_RESPONSE_REPOSITORY)->findOneBy(array("messageNumber"=>$messageNumber));;

            $response->setIsRemovedByUser($content['is_removed_by_user']);
        //TODO NOTE! No "HideMessage" declaration message is created-and-persisted. A value is just updated in an existing declaration.
            //First Persist object to Database, before sending it to the queue
            $this->persist($response, Utils::getClassName($response));

            return new JsonResponse(array("code"=>200, "message"=>"saved"), 200);
        }

        return new JsonResponse(array('code' => 428, "message" => "fill in message number"), 428);
    }
}