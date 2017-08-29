<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\ErrorMessageAPIControllerInterface;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseRepositoryInterface;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\RequestTypeIRDutchInformal;
use AppBundle\Enumerator\RequestTypeIRDutchOfficial;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\Validator;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ErrorMessageService
 * @package AppBundle\Service
 */
class ErrorMessageService extends ControllerServiceBase implements ErrorMessageAPIControllerInterface
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getErrors(Request $request)
    {
        if(!AdminValidator::isAdmin($this->getEmployee(),AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $showHiddenForAdmin = RequestUtil::getBooleanQuery($request,QueryParameter::SHOW_HIDDEN,false);
        return ResultUtil::successResult($this->getManager()->getRepository(DeclareBase::class)->getErrorsOverview($showHiddenForAdmin));
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function getErrorDetails(Request $request, $messageId)
    {
        return $this->getErrorDetailsBase($request, $messageId, $this->getManager()->getRepository(DeclareBase::class));
    }


    /**
     * @param Request $request
     * @param $messageId
     * @return JsonResponse
     */
    public function getErrorDetailsNonIRmessage(Request $request, $messageId)
    {
        return $this->getErrorDetailsBase($request, $messageId, $this->getManager()->getRepository(DeclareNsfoBase::class));
    }


    /**
     * @param Request $request
     * @param $messageId
     * @param DeclareBaseRepositoryInterface $repository
     * @return JsonResponse
     */
    public function getErrorDetailsBase(Request $request, $messageId, $repository)
    {
        if(!AdminValidator::isAdmin($this->getEmployee(),AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }

        $declare = $repository->getErrorDetails($messageId);
        if ($declare instanceof JsonResponse) { return $declare; }

        $output = $this->getBaseSerializer()->getDecodedJson($declare, [JmsGroup::ERROR_DETAILS]);
        return ResultUtil::successResult($output);
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
            $this->getConnection()->exec($sql);

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
            $nsfoMessage = $this->getManager()->getRepository(DeclareNsfoBase::class)->findOneByMessageId($messageId);

            $nsfoMessage->setIsHidden($isHidden);
            $this->getManager()->persist($nsfoMessage);
            $this->getManager()->flush();

            return new JsonResponse(["code"=>200, "message"=>"saved"], 200);
        }

        return new JsonResponse(['code' => 428, "message" => "fill in messageId and hidden boolean"], 428);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateHideStatus(Request $request)
    {
        $employee = $this->getEmployee();
        $content = RequestUtil::getContentAsArray($request);

        /* Validation */

        if ($content->containsKey(JsonInputConstant::HIDE_FOR_ADMIN) &&
            $employee === null) {
            return Validator::createJsonResponse('Only admins may hide error messages for admins', 401);
        }

        $necessaryKeys = [
            JsonInputConstant::MESSAGE_ID,
            JsonInputConstant::IS_IR_MESSAGE,
        ];

        $optionalKeys = [
            JsonInputConstant::HIDE_FOR_ADMIN,
            JsonInputConstant::IS_HIDDEN,
        ];

        $validationResults = [
            RequestUtil::contentContainsNecessaryKeys($necessaryKeys, $content),
            RequestUtil::contentContainsAtLeastOneKey($optionalKeys, $content),
        ];

        foreach ($validationResults as $validationResult) {
            if ($validationResult instanceof JsonResponse) {
                return $validationResult;
            }
        }


        $messageId = $content->get(JsonInputConstant::MESSAGE_ID);
        if (!is_string($messageId) && !is_int($messageId)) { ResultUtil::errorResult('message_id must be a string', 428); }

        $isIrMessage = $content->get(JsonInputConstant::IS_IR_MESSAGE);
        if ($isIrMessage) {
            /** @var DeclareBase $declare */
            $declare = $this->getManager()->getRepository(DeclareBase::class)->findOneByMessageId($messageId);
        } else {
            /** @var DeclareNsfoBase $declare */
            $declare = $this->getManager()->getRepository(DeclareNsfoBase::class)->findOneByMessageId($messageId);
        }

        if ($declare === null) {
            $prefix = $isIrMessage ? '' : 'non-';
            return ResultUtil::errorResult('No '.$prefix.'IR declare found for given messageId: '.$messageId, 428);
        }

        /* update values */

        $anyValueUpdated = false;
        $hideForAdmin = $content->get(JsonInputConstant::HIDE_FOR_ADMIN);
        if (is_bool($hideForAdmin) && $hideForAdmin !== $declare->isHideForAdmin()) {
            $declare->setHideForAdmin($hideForAdmin);
            $anyValueUpdated = true;
        }

        $isHidden = $content->get(JsonInputConstant::IS_HIDDEN);
        if ($isIrMessage) {
            if (is_bool($isHidden) && $isHidden !== $declare->isHideFailedMessage()) {
                $declare->setHideFailedMessage($isHidden);
                $anyValueUpdated = true;
            }
        } else {
            if (is_bool($isHidden) && $isHidden !== $declare->getIsHidden()) {
                $declare->setIsHidden($isHidden);
                $anyValueUpdated = true;
            }
        }

        if ($anyValueUpdated) {
            $this->getManager()->persist($declare);
            $this->getManager()->flush();
        }

        $jmsGroups = [JmsGroup::HIDDEN_STATUS];
        if ($employee) { $jmsGroups[] = JmsGroup::ADMIN_HIDDEN_STATUS; }

        $output = $this->getBaseSerializer()->getDecodedJson($declare, $jmsGroups);
        return ResultUtil::successResult($output);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getDutchDeclareTypes(Request $request)
    {
        $isFormal = RequestUtil::getBooleanQuery($request, QueryParameter::FORMAL, false);
        $output = $isFormal ? RequestTypeIRDutchOfficial::getConstants() : RequestTypeIRDutchInformal::getConstants();
        return ResultUtil::successResult($output);
    }
}