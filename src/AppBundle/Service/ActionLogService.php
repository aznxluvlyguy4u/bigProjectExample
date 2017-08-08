<?php


namespace AppBundle\Service;


use AppBundle\Entity\ActionLog;
use AppBundle\Entity\ActionLogRepository;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Util\ResultUtil;
use AppBundle\Validation\AdminValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;


class ActionLogService
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var ActionLogRepository */
    private $actionLogRepository;

    /**
     * ActionLogService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
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
     * @param Person $user
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getActionLogs(Request $request, Person $user)
    {
        if(!AdminValidator::isAdmin($user, AccessLevelType::ADMIN)) {
            return AdminValidator::getStandardErrorResponse();
        }
        
        //TODO
    }



}