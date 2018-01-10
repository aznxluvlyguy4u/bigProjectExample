<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\ErrorMessageAPIControllerInterface;
use AppBundle\Entity\DeclareBase;
use AppBundle\Entity\DeclareBaseRepositoryInterface;
use AppBundle\Entity\DeclareNsfoBase;
use AppBundle\Entity\Employee;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Enumerator\RequestTypeIRDutchInformal;
use AppBundle\Enumerator\RequestTypeIRDutchOfficial;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ErrorMessageService
 * @package AppBundle\Service
 */
class ErrorMessageService extends ControllerServiceBase implements ErrorMessageAPIControllerInterface
{
    const MULTI_EDIT_BATCH_SIZE = 100;

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

        /* Validation: content format */
        $validationResult = $this->validateUpdateHideStatusContent($content, $employee);
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        /* Update values with database data validation */

        if (RequestUtil::isMultiEdit($content)) {
            $successCount = 0;
            $errorCount = 0;
            $multiOutput = [];

            foreach ($content->get(JsonInputConstant::EDITS) as $edit) {
                $output = $this->updateSingleHideStatus($edit, $employee, false);

                if ($output instanceof JsonResponse) {
                    $errorCount++;
                    continue;
                }

                if ($successCount++%self::MULTI_EDIT_BATCH_SIZE === 0) {
                    $this->getManager()->flush();
                }

                $messageId = ArrayUtil::get(JsonInputConstant::MESSAGE_ID, $edit, null);
                if ($messageId) {
                    $isIrMessageAsString = ArrayUtil::get(JsonInputConstant::IS_IR_MESSAGE, $edit) ? 'isRvo' : 'nonRvo';
                    $key = $messageId.'_'.$isIrMessageAsString;
                    $multiOutput[$key] = $output;
                } else {
                    $multiOutput[] = $output;
                }
            }
            $this->getManager()->flush();

            $result = [
                JsonInputConstant::SUCCESS_COUNT => $successCount,
                JsonInputConstant::ERROR_COUNT => $errorCount,
                JsonInputConstant::SUCCESSFUL_EDITS => array_values($multiOutput),
            ];

            if ($errorCount > 0) {
                return ResultUtil::multiEditErrorResult(
                    $result,
                    ucfirst(strtolower($this->translator->transChoice('THERE WAS ONE FAILED UPDATE|THERE WERE %count% UPDATE', $errorCount))),
                    Response::HTTP_BAD_REQUEST
                );
            }

            return ResultUtil::successResult($result);
        }

        // Single Edit
        $output = $this->updateSingleHideStatus($content, $employee);
        if ($output instanceof JsonResponse) {
            return $output;
        }
        return ResultUtil::successResult($output);
    }


    /**
     * @param ArrayCollection|array $content
     * @param Employee $employee
     * @param boolean $flush
     * @return JsonResponse|DeclareBase|DeclareNsfoBase
     */
    private function updateSingleHideStatus($content, $employee, $flush = true)
    {
        if ($content instanceof ArrayCollection) {
            $content = $content->toArray();
        }

        $declare = $this->getDeclareFromMessageIdAndIsIrMessage($content);
        if ($declare instanceof JsonResponse) {
            return $declare;
        }

        $anyValueUpdated = false;
        $hideForAdmin = ArrayUtil::get(JsonInputConstant::HIDE_FOR_ADMIN, $content);
        if (is_bool($hideForAdmin) && $hideForAdmin !== $declare->isHideForAdmin()) {
            $declare->setHideForAdmin($hideForAdmin);
            $anyValueUpdated = true;
        }

        $isIrMessage = ArrayUtil::get(JsonInputConstant::IS_IR_MESSAGE, $content);
        $isHidden = ArrayUtil::get(JsonInputConstant::IS_HIDDEN, $content);
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
            if ($flush) {
                $this->getManager()->flush();
            }
        }

        $jmsGroups = [JmsGroup::HIDDEN_STATUS];
        if ($employee) { $jmsGroups[] = JmsGroup::ADMIN_HIDDEN_STATUS; }

        return $this->getBaseSerializer()->getDecodedJson($declare, $jmsGroups);
    }


    /**
     * @param ArrayCollection $content
     * @param Employee $employee
     * @return JsonResponse|true
     */
    private function validateUpdateHideStatusContent(ArrayCollection $content, $employee)
    {
        if(RequestUtil::isMultiEdit($content)) {
            $edits = $content->get(JsonInputConstant::EDITS);
            if (!is_array($edits) || count($edits) === 0) {
                return ResultUtil::errorResult('There are no edits', 428);
            }

            foreach ($edits as $edit) {
                // For a multi edit, validate if declare exists during the edit process, to prevent huge multiple database retrievals
                $validationResult = $this->validateUpdateHideStatusSingleEditContent($edit, $employee);
                if ($validationResult instanceof JsonResponse) {
                    return $validationResult;
                }
            }
            return true;

        } else {
            return $this->validateUpdateHideStatusSingleEditContent($content, $employee);
        }
    }


    /**
     * @param ArrayCollection|array $content
     * @param Employee $employee
     * @return JsonResponse|true
     */
    private function validateUpdateHideStatusSingleEditContent($content, $employee)
    {
        if ($content instanceof ArrayCollection) {
            $content = $content->toArray();
        }

        if (key_exists(JsonInputConstant::HIDE_FOR_ADMIN, $content) && $employee === null) {
            return ResultUtil::errorResult('Only admins may hide error messages for admins', 401);
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

        return true;
    }


    /**
     * @param array $content
     * @return JsonResponse|DeclareBase|DeclareNsfoBase
     */
    private function getDeclareFromMessageIdAndIsIrMessage(array $content)
    {
        $messageId = ArrayUtil::get(JsonInputConstant::MESSAGE_ID, $content);
        if (!is_string($messageId)) {
            ResultUtil::errorResult('message_id must be a string', 428);
        }

        $isIrMessage = ArrayUtil::get(JsonInputConstant::IS_IR_MESSAGE, $content);
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

        return $declare;
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