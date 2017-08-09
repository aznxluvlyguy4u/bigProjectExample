<?php

namespace AppBundle\Service;


use AppBundle\Entity\ActionLog;
use AppBundle\Entity\ActionLogRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ActionLogService
 */
class ActionLogService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var IRSerializer */
    private $serializer;
    /** @var UserService */
    private $userService;

    /** @var ActionLogRepository */
    private $actionLogRepository;

    /**
     * ActionLogService constructor.
     * @param EntityManagerInterface $em
     * @param IRSerializer $serializer
     * @param UserService $userService
     */
    public function __construct(EntityManagerInterface $em, IRSerializer $serializer, UserService $userService)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->userService = $userService;

        $this->actionLogRepository = $em->getRepository(ActionLog::class);
    }


    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function getUserActionTypes(Request $request)
    {
        $user = $this->userService->getUser();

        if(AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
            $userAccountId = RequestUtil::getIntegerQuery($request,QueryParameter::USER_ACCOUNT_ID);
        } else {
            //Regular Clients are only allowed to see their own log
            $userAccountId = $user->getId();
        }

        return ResultUtil::successResult($this->actionLogRepository->getUserActionTypes($userAccountId));
    }


    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getActionLogs(Request $request)
    {
        $user = $this->userService->getUser();

        $startDate = RequestUtil::getDateQuery($request, QueryParameter::START_DATE);
        $endDate = RequestUtil::getDateQuery($request, QueryParameter::END_DATE);
        $userActionType = $request->query->get(QueryParameter::USER_ACTION_TYPE);

        if(AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
            $userAccountId = RequestUtil::getIntegerQuery($request,QueryParameter::USER_ACCOUNT_ID);
            $jmsGroup = JmsGroup::ACTION_LOG_ADMIN;
        } else {
            //Regular Clients are only allowed to see their own log
            $userAccountId = $user->getId();
            $jmsGroup = JmsGroup::ACTION_LOG_USER;
        }

        $actionLogs = $this->actionLogRepository->findByDateTypeAndUserId($startDate, $endDate, $userActionType, $userAccountId);
        return ResultUtil::successResult($this->serializer->getDecodedJson($actionLogs, $jmsGroup));
    }



}