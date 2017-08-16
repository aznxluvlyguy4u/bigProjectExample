<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Client;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Location;
use AppBundle\Entity\Person;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;

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
     * @param ObjectManager $em
     * @param Employee $admin
     * @param Request $request
     * @param TreatmentTemplate $template
     * @return ActionLog
     */
    public static function createTreatmentTemplate(ObjectManager $em, $admin, $request, $template)
    {
        $description = 'Type: '.$template->getDutchType().'('.$template->getType().')'
            .', '.ArrayUtil::implode(RequestUtil::getContentAsArray($request)->toArray());

        $log = new ActionLog($template->getLocationOwner(), $admin, UserActionType::TREATMENT_TEMPLATE_CREATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param ObjectManager $em
     * @param Client $accountOwner
     * @param Employee $admin
     * @param string $description
     * @return ActionLog
     */
    public static function editTreatmentTemplate(ObjectManager $em, $accountOwner, $admin, $description)
    {
        $log = new ActionLog($accountOwner, $admin, UserActionType::TREATMENT_TEMPLATE_EDIT, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param ObjectManager $em
     * @param Client $accountOwner
     * @param Employee $admin
     * @param TreatmentTemplate $template
     * @return ActionLog
     */
    public static function deleteTreatmentTemplate(ObjectManager $em, $accountOwner, $admin, $template)
    {
        $description =
            'id: '.$template->getId()
            .', type: '.$template->getDutchType()
            .', ubn: '.$template->getUbn('')
            .', beschrijving: '.$template->getDescription()
        ;

        $log = new ActionLog($accountOwner, $admin, UserActionType::TREATMENT_TEMPLATE_DELETE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }
}