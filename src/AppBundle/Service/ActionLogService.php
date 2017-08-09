<?php


namespace AppBundle\Service;


use AppBundle\Entity\ActionLog;
use AppBundle\Entity\ActionLogRepository;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\QueryParameter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


class ActionLogService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var UserService */
    private $userService;

    /** @var ActionLogRepository */
    private $actionLogRepository;

    /**
     * ActionLogService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em, UserService $userService)
    {
        $this->em = $em;
        $this->userService = $userService;

        $this->actionLogRepository = $em->getRepository(ActionLog::class);
    }


    /**
     * @return \AppBundle\Component\HttpFoundation\JsonResponse
     */
    public function getUserActionTypes()
    {
        return ResultUtil::successResult($this->actionLogRepository->getUserActionTypes());
    }


    /**
     * TODO
     *
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
        } else {
            //Regular Clients are only allowed to see their own log
            $userAccountId = $user->getId();
        }

        $actionLogs = $this->actionLogRepository->findByDateTypeAndUserId($startDate, $endDate, $userActionType, $userAccountId);
        return ResultUtil::successResult($actionLogs);
    }



}