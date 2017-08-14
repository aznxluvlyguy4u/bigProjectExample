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


    /**
     * Note password change is saved in a separate log
     *
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function editOwnAdminProfile(ObjectManager $om, $actionBy, $content)
    {
        $oldFirstName = $content->get(JsonInputConstant::FIRST_NAME);
        $oldLastName = $content->get(JsonInputConstant::LAST_NAME);
        $oldEmailAddress = $content->get(JsonInputConstant::EMAIL_ADDRESS);

        $message = '';

        $changes = [
            //old value                   new value
            $actionBy->getFirstName() => $content->get(JsonInputConstant::FIRST_NAME),
            $actionBy->getLastName() => $content->get(JsonInputConstant::LAST_NAME),
            $actionBy->getEmailAddress() => $content->get(JsonInputConstant::EMAIL_ADDRESS),
        ];

        $anyChanges = false;
        $prefix = '';
        foreach ($changes as $oldValue => $newValue) {
            if ($oldValue !== $newValue) {
                $anyChanges = true;
                $message = $message . $prefix. $oldValue . ' => ' .$newValue;
                $prefix = ', ';
            }
        }

        if ($anyChanges) {
            $log = new ActionLog($actionBy, $actionBy, UserActionType::EDIT_ADMIN, false, $message, self::IS_USER_ENVIRONMENT);
            DoctrineUtil::persistAndFlush($om, $log);
            return $log;
        }

        return null;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $admin
     * @return ActionLog
     */
    public static function passwordChangeAdminInProfile(ObjectManager $om, $admin)
    {
        $log = new ActionLog($admin, $admin, UserActionType::ADMIN_PASSWORD_CHANGE, false,null, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function createAdmin(ObjectManager $om, $actionBy, $content)
    {
        $firstName = $content->get('first_name');
        $lastName = $content->get('last_name');
        $emailAddress = $content->get('email_address');
        $accessLevel = $content->get('access_level');

        $message = $accessLevel.'| '.$emailAddress.' : '.$firstName.' '.$lastName;

        $log = new ActionLog(null, $actionBy, UserActionType::CREATE_ADMIN, false, $message, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param ArrayCollection $content
     * @return ActionLog
     */
    public static function editAdmin(ObjectManager $om, $actionBy, $content)
    {
        $personId = $content->get('person_id');

        $message = '';
        $oldFirstName = '';
        $oldLastName = '';
        $oldEmailAddress = '';
        $oldAccessLevel = '';

        $admin = null;
        if ($personId) {
            $admin = $om->getRepository(Employee::class)->findOneBy(['personId' => $personId]);
            if ($admin) {
                $oldFirstName = $admin->getFirstName();
                $oldLastName = $admin->getLastName();
                $oldEmailAddress = $admin->getEmailAddress();
                $oldAccessLevel = $admin->getAccessLevel();
            }
        }

        $changes = [
            //old value       new value
            $oldFirstName => $content->get('first_name'),
            $oldLastName => $content->get('last_name'),
            $oldEmailAddress => $content->get('email_address'),
            $oldAccessLevel => $content->get('access_level'),
        ];

        $anyChanges = false;
        $prefix = '';
        foreach ($changes as $oldValue => $newValue) {
            if ($oldValue !== $newValue) {
                $anyChanges = true;
                $message = $message . $prefix. $oldValue . ' => ' .$newValue;
                $prefix = ', ';
            }
        }

        if ($anyChanges) {
            $log = new ActionLog($admin, $actionBy, UserActionType::EDIT_ADMIN, false, $message, self::IS_USER_ENVIRONMENT);
            DoctrineUtil::persistAndFlush($om, $log);
            return $log;
        }

        return null;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param Employee $adminToDeactivate
     * @return ActionLog
     */
    public static function deactivateAdmin(ObjectManager $om, $actionBy, $adminToDeactivate)
    {
        $userActionType = UserActionType::DEACTIVATE_ADMIN;
        if($adminToDeactivate instanceof Employee) {
            $message = $adminToDeactivate->getEmailAddress().' | '.$adminToDeactivate->getFullName();
        } else {
            $message = 'No admin to deactivate found';
        }

        $log = new ActionLog($adminToDeactivate, $actionBy, $userActionType, false, $message, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param ActionLog $log
     * @param Employee $admin
     * @return ActionLog
     */
    public static function completeAdminCreateOrEditActionLog(ObjectManager $om, ActionLog $log, $admin)
    {
        if ($log !== null) {
            $log->setUserAccount($admin);
            $log->setIsCompleted(true);
            DoctrineUtil::persistAndFlush($om, $log);
        }

        return $log;
    }
}