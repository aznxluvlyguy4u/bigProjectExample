<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\ErrorMessageAPIControllerInterface;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ErrorMessageService
 * @package AppBundle\Service
 */
class ErrorMessageService extends ControllerServiceBase implements ErrorMessageAPIControllerInterface
{

    /**
     * ErrorMessageService constructor.
     * @param EntityManagerInterface $em
     * @param IRSerializer $serializer
     * @param CacheService $cacheService
     * @param UserService $userService
     */
    public function __construct(EntityManagerInterface $em, IRSerializer $serializer, CacheService $cacheService, UserService $userService)
    {
        parent::__construct($em, $serializer, $cacheService, $userService);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getErrors(Request $request)
    {
        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function getErrorDetails(Request $request, $messageId)
    {
        return ResultUtil::successResult('ok');
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateError(Request $request) {
        $content = RequestUtil::getContentAsArray($request);
        $requestId = $content->get("request_id");
        $isRemovedByUserBoolean = $content['is_removed_by_user'];

        if($requestId != null) {

            $sql = "UPDATE declare_base SET hide_failed_message = ".StringUtil::getBooleanAsString($isRemovedByUserBoolean)."
            WHERE request_id = '".$requestId."'";
            $this->conn->exec($sql);

            return new JsonResponse(array("code"=>200, "message"=>"saved"), 200);
        }

        return new JsonResponse(array('code' => 428, "message" => "fill in message number"), 428);
    }


    /**
     * @param Request $request
     * @param string $messageId
     * @return JsonResponse
     */
    public function updateNsfoDeclarationError(Request $request, $messageId) {
        $content = RequestUtil::getContentAsArray($request);
        $isHidden = $content->get(JsonInputConstant::IS_HIDDEN);

        if($messageId !== null && $isHidden !== null) {

            /** @var DeclareNsfoBase $nsfoMessage */
            $nsfoMessage = $this->declareNsfoBaseRepository->findOneByMessageId($messageId);

            $nsfoMessage->setIsHidden($isHidden);
            $this->em->persist($nsfoMessage);
            $this->em->flush();

            return new JsonResponse(["code"=>200, "message"=>"saved"], 200);
        }

        return new JsonResponse(['code' => 428, "message" => "fill in messageId and hidden boolean"], 428);
    }
}