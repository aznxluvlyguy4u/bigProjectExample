<?php

namespace AppBundle\Service;


use AppBundle\Entity\ActionLog;
use AppBundle\Entity\ActionLogRepository;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\Validator;
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
    /** @var CacheService */
    private $cacheService;

    /** @var ActionLogRepository */
    private $actionLogRepository;

    public function __construct(EntityManagerInterface $em, IRSerializer $serializer,
                                UserService $userService, CacheService $cacheService)
    {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->userService = $userService;
        $this->cacheService = $cacheService;

        $this->actionLogRepository = $em->getRepository(ActionLog::class);
    }


    /**
     * @param Request $request
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function getUserActionTypes(Request $request)
    {
        $user = $this->userService->getUser();
        $accountOwner = $this->userService->getAccountOwner($request);

        if(AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
            if ($accountOwner) { //GhostLogin
                $userAccountId = $accountOwner->getPersonId();

            } else { //Admin in Admin environment
                $userAccountId =  $request->query->get(QueryParameter::USER_ACCOUNT_ID);
            }
        } else {
            //Regular Clients are only allowed to see their own log
            $userAccountId = $user->getPersonId();
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
        $accountOwner = $this->userService->getAccountOwner($request);

        $startDate = RequestUtil::getDateQuery($request, QueryParameter::START_DATE);
        $endDate = RequestUtil::getDateQuery($request, QueryParameter::END_DATE);
        $userActionType = $request->query->get(QueryParameter::USER_ACTION_TYPE);

        if(AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
            if ($accountOwner) { //GhostLogin
                $userAccountId = $accountOwner->getId();
                $jmsGroup = JmsGroup::ACTION_LOG_USER;

            } else { //Admin in Admin environment
                $userAccountPersonId =  $request->query->get(QueryParameter::USER_ACCOUNT_ID);
                $userAccountId = $this->em->getRepository(Person::class)->getIdByPersonId($userAccountPersonId);
                $jmsGroup = JmsGroup::ACTION_LOG_ADMIN;
            }
        } else {
            //Regular Clients are only allowed to see their own log
            $userAccountId = $user->getId();
            $jmsGroup = JmsGroup::ACTION_LOG_USER;
        }
        $actionLogs = $this->actionLogRepository->findByDateTypeAndUserId($startDate, $endDate, $userActionType, $userAccountId);
        return ResultUtil::successResult($this->serializer->getDecodedJson($actionLogs, $jmsGroup));
    }


    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAccountOwnerPersonIds()
    {
        if(!AdminValidator::isAdmin($this->userService->getUser(), AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }
        return ResultUtil::successResult($this->actionLogRepository->getUserAccountPersonIds($this->serializer, $this->cacheService));
    }
}