<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class AdminActionLogWriter
{
    const IS_USER_ENVIRONMENT = false;

    /**
     * @param ObjectManager $om
     * @param Employee $loggedInAdmin
     * @param Location $location
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function updateHealthStatus(ObjectManager $om, $loggedInAdmin, $location, $content)
    {
        $newMaediVisnaStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_STATUS, $content);
        $newScrapieStatus = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::SCRAPIE_STATUS, $content);
        $ubn = NullChecker::getUbnFromLocation($location);
        $client = $location->getCompany()->getOwner();

        $description = 'new health statusses for ubn: '.$ubn.'. '.'maedi visna: '.$newMaediVisnaStatus.'. scrapie: '.$newScrapieStatus;

        $log = new ActionLog($client, $loggedInAdmin, UserActionType::HEALTH_STATUS_UPDATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $loggedInAdmin
     * @param string $description
     * @return ActionLog
     */
    public static function updateDashBoardIntro(ObjectManager $om, $loggedInAdmin, $description)
    {
        $log = new ActionLog(null, $loggedInAdmin, UserActionType::DASHBOARD_INTRO_TEXT_UPDATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $loggedInAdmin
     * @param string $description
     * @return ActionLog
     */
    public static function updateContactInfo(ObjectManager $om, $loggedInAdmin, $description)
    {
        $log = new ActionLog(null, $loggedInAdmin, UserActionType::CONTACT_INFO_UPDATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }
}