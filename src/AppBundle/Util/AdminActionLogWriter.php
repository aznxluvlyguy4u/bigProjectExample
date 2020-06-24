<?php

namespace AppBundle\Util;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\ActionLog;
use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalResidence;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Exterior;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthInspection;
use AppBundle\Entity\Person;
use AppBundle\Entity\RetrieveAnimals;
use AppBundle\Entity\Treatment;
use AppBundle\Entity\TreatmentMedication;
use AppBundle\Entity\TreatmentTemplate;
use AppBundle\Entity\TreatmentType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\UserActionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class AdminActionLogWriter
{
    const IS_USER_ENVIRONMENT = false;
    const NULL_REPLACEMENT_TEXT = 'LEEG';
    const DATE_FORMAT = 'Y-m-d';

    const DESCRIPTION_MID_KEY_DUTCH = ', beschrijving: ';
    const INSPECTOR_MID_KEY = ', Inspecteur: ';

    /**
     * @param EntityManagerInterface $em
     * @param Client $client
     * @param RetrieveAnimals $retrieveAnimals
     */
    public static function rvoLeadingAnimalSync(EntityManagerInterface $em, Client $client, RetrieveAnimals $retrieveAnimals)
    {
        $log = new ActionLog($client, $retrieveAnimals->getActionBy(), UserActionType::RVO_LEADING_ANIMAL_SYNC, true, $retrieveAnimals->getUbn(), self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);
    }


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
        $maediVisnaReasonOfEdit = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_REASON_OF_EDIT, $content);
        $scrapieReasonOfEdit = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::MAEDI_VISNA_REASON_OF_EDIT, $content);
        $ubn = NullChecker::getUbnFromLocation($location);
        $client = $location->getCompany()->getOwner();

        if ($maediVisnaReasonOfEdit !== null && $maediVisnaReasonOfEdit !== '') {
            $maediVisnaReasonOfEdit = ' ('.$maediVisnaReasonOfEdit.')';
        }

        if ($scrapieReasonOfEdit !== null && $scrapieReasonOfEdit !== '') {
            $scrapieReasonOfEdit = ' ('.$scrapieReasonOfEdit.')';
        }

        $description = 'new health statusses for ubn: '.$ubn.'. '
            .'maedi visna: '.$newMaediVisnaStatus.$maediVisnaReasonOfEdit
            .'. scrapie: '.$newScrapieStatus. $scrapieReasonOfEdit
            .'.';

        $log = new ActionLog($client, $loggedInAdmin, UserActionType::HEALTH_STATUS_UPDATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Employee $loggedInAdmin
     * @param Company $company
     * @return ActionLog
     */
    public static function updateAnimalHealthSubscription(EntityManagerInterface $em, Employee $loggedInAdmin,
                                                          Company $company)
    {
        $description = $company->getAnimalHealthSubscription() ? 'ACTIVATED' : 'DEACTIVATED';

        $log = new ActionLog($company->getOwner(), $loggedInAdmin, UserActionType::ANIMAL_HEALTH_SUBSCRIPTION_UPDATE,
            true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

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
            .', '.ArrayUtil::implode(RequestUtil::getContentAsArrayCollection($request)->toArray());

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
        return self::editTreatmentBase($em, $accountOwner, $admin, $description, UserActionType::TREATMENT_TEMPLATE_EDIT);
    }


    /**
     * @param ObjectManager $em
     * @param Client $accountOwner
     * @param Employee $admin
     * @param string $description
     * @return ActionLog
     */
    private static function editTreatmentBase(ObjectManager $em, $accountOwner, $admin, $description, $userActionType)
    {
        $log = new ActionLog($accountOwner, $admin, $userActionType, true, $description, self::IS_USER_ENVIRONMENT);
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
            .self::DESCRIPTION_MID_KEY_DUTCH.$template->getDescription()
        ;

        $log = new ActionLog($accountOwner, $admin, UserActionType::TREATMENT_TEMPLATE_DELETE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Employee $admin
     * @param TreatmentMedication $medication
     * @return ActionLog
     */
    public static function deleteTreatmentMedication(EntityManagerInterface $em, $admin, $medication)
    {
        $description =
            'id: '.$medication->getId()
            .', regNl: '.$medication->getRegNl()
            .self::DESCRIPTION_MID_KEY_DUTCH.$medication->getName()
        ;

        $log = new ActionLog(null, $admin, UserActionType::TREATMENT_MEDICATION_DELETE, true, $description, self::IS_USER_ENVIRONMENT);
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
    public static function reactivateTreatmentTemplate(ObjectManager $em, $accountOwner, $admin, $template)
    {
        $description =
            'id: '.$template->getId()
            .', type: '.$template->getDutchType()
            .', ubn: '.$template->getUbn('')
            .self::DESCRIPTION_MID_KEY_DUTCH.$template->getDescription()
        ;

        $log = new ActionLog($accountOwner, $admin, UserActionType::TREATMENT_TEMPLATE_REACTIVATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param ObjectManager $em
     * @param Employee $admin
     * @param Request $request
     * @param TreatmentType $treatmentType
     * @return ActionLog
     */
    public static function createTreatmentType(ObjectManager $em, $admin, $request, $treatmentType)
    {
        $description = $treatmentType->getDutchType().'('.$treatmentType->getType().'): '.$treatmentType->getDescription();

        $log = new ActionLog(null, $admin, UserActionType::TREATMENT_TYPE_CREATE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param ObjectManager $em
     * @param Employee $admin
     * @param string $description
     * @return ActionLog
     */
    public static function editTreatmentType(ObjectManager $em, $admin, $description)
    {
        return self::editTreatmentBase($em, null, $admin, $description, UserActionType::TREATMENT_TYPE_EDIT);
    }


    /**
     * @param ObjectManager $em
     * @param Employee $admin
     * @param TreatmentType $treatmentType
     * @return ActionLog
     */
    public static function deleteTreatmentType(ObjectManager $em, $admin, $treatmentType)
    {
        $description =
            'id: '.$treatmentType->getId()
            .', type: '.$treatmentType->getDutchType()
            .self::DESCRIPTION_MID_KEY_DUTCH.$treatmentType->getDescription()
        ;

        $log = new ActionLog(null, $admin, UserActionType::TREATMENT_TYPE_DELETE, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

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
     * @param LocationHealthInspection $inspection
     * @return ActionLog
     */
    public static function createInspection(ObjectManager $om, $actionBy, $inspection)
    {
        $ubn = $inspection->getLocation() !== null ? 'ubn: ' . $inspection->getLocation()->getUbn().', '  : '';

        $description = $ubn
            . $inspection->getInspectionSubject()
            . $inspection->getOrderNumber()
            . $inspection->getRequestDate()->format(self::DATE_FORMAT)
            . $inspection->getStatus()
        ;

        $log = new ActionLog(null, $actionBy, UserActionType::CREATE_INSPECTION, true, $description, self::IS_USER_ENVIRONMENT);

        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Employee $actionBy
     * @param LocationHealthInspection $inspection
     * @return ActionLog
     */
    public static function changeInspectionStatus(ObjectManager $om, $actionBy, $inspection)
    {
        $ubn = $inspection->getLocation() !== null ? 'ubn: ' . $inspection->getLocation()->getUbn().', '  : '';

        $description = $ubn
            . $inspection->getInspectionSubject()
            . $inspection->getOrderNumber()
            . $inspection->getRequestDate()->format(self::DATE_FORMAT)
            . $inspection->getStatus()
        ;

        $log = new ActionLog(null, $actionBy, UserActionType::CHANGE_INSPECTION_STATUS, true, $description, self::IS_USER_ENVIRONMENT);

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



    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Employee $actionBy
     * @param Exterior $exterior
     * @return ActionLog
     * @throws \Exception
     */
    public static function createExterior(ObjectManager $om, $client, $actionBy, $exterior)
    {
        if ($exterior->getAnimal() === null) { throw new \Exception('Exterior must have an Animal. Exterior id: ',$exterior->getId()); }

        $inspectorName = $exterior->getInspector() !== null ? self::INSPECTOR_MID_KEY . $exterior->getInspector()->getFullName() : '';

        $description = $exterior->getAnimal()->getUln() . ' '. $exterior->getMeasurementDate()->format(self::DATE_FORMAT) . ' '
            . $exterior->getKind()
            . ', KOP ' . $exterior->getSkull()
            . ', ONT ' . $exterior->getProgress()
            . ', BES ' . $exterior->getMuscularity()
            . ', EVE ' . $exterior->getProportion()
            . ', TYP ' . $exterior->getExteriorType()
            . ', BEE ' . $exterior->getLegWork()
            . ', VAC ' . $exterior->getFur()
            . ', ALG ' . $exterior->getGeneralAppearance()
            . ', SHT ' . $exterior->getHeight()
            . ', LGT ' . $exterior->getTorsoLength()
            . ', BDP ' . $exterior->getBreastDepth()
            . ', MRK ' . $exterior->getMarkings()
            . $inspectorName
        ;

        $log = new ActionLog($client, $actionBy, UserActionType::CREATE_EXTERIOR, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Employee $actionBy
     * @param Exterior $newExterior
     * @param Exterior $oldExterior
     * @return ActionLog
     * @throws \Exception
     */
    public static function updateExterior(ObjectManager $om, $client, $actionBy, $newExterior, $oldExterior)
    {
        if ($newExterior->getAnimal() === null) { throw new \Exception('Exterior must have an Animal. Exterior id: ',$newExterior->getId()); }

        $inspectorName = $newExterior->getInspector() !== null ? self::INSPECTOR_MID_KEY . $newExterior->getInspector()->getFullName() : '';

        $description = '';
        $prefix = '';

        if ($newExterior->getAnimal()->getUln() !== $oldExterior->getAnimal()->getUln()) {
            $description = $description . $prefix . $oldExterior->getAnimal()->getUln() . ' => '.$newExterior->getAnimal()->getUln();
            $prefix = ', ';
        } else {
            $description = $description . $prefix . $newExterior->getAnimal()->getUln().': ';
            $prefix = ', ';
        }

        if ($newExterior->getMeasurementDate() !== $oldExterior->getMeasurementDate()) {
            $description = $description . $prefix . $oldExterior->getMeasurementDate()->format(self::DATE_FORMAT)
                . ' => '.$newExterior->getMeasurementDate()->format(self::DATE_FORMAT);
            $prefix = ', ';
        }

        if ($newExterior->getKind() !== $oldExterior->getKind()) {
            $description = $description . $prefix . $oldExterior->getKind() . ' => '.$newExterior->getKind();
            $prefix = ', ';
        }

        if ($newExterior->getSkull() !== $oldExterior->getSkull()) {
            $description = $description . $prefix . 'KOP ' .$oldExterior->getKind() . ' => '.$newExterior->getKind();
            $prefix = ', ';
        }

        if ($newExterior->getProgress() !== $oldExterior->getProgress()) {
            $description = $description . $prefix . $oldExterior->getProgress() . ' => '.$newExterior->getProgress();
            $prefix = ', ';
        }

        if ($newExterior->getMuscularity() !== $oldExterior->getMuscularity()) {
            $description = $description . $prefix . $oldExterior->getMuscularity() . ' => '.$newExterior->getMuscularity();
            $prefix = ', ';
        }

        if ($newExterior->getProportion() !== $oldExterior->getProportion()) {
            $description = $description . $prefix . $oldExterior->getProportion() . ' => '.$newExterior->getProportion();
            $prefix = ', ';
        }

        if ($newExterior->getExteriorType() !== $oldExterior->getExteriorType()) {
            $description = $description . $prefix . $oldExterior->getExteriorType() . ' => '.$newExterior->getExteriorType();
            $prefix = ', ';
        }

        if ($newExterior->getLegWork() !== $oldExterior->getLegWork()) {
            $description = $description . $prefix . $oldExterior->getLegWork() . ' => '.$newExterior->getLegWork();
            $prefix = ', ';
        }

        if ($newExterior->getFur() !== $oldExterior->getFur()) {
            $description = $description . $prefix . $oldExterior->getFur() . ' => '.$newExterior->getFur();
            $prefix = ', ';
        }

        if ($newExterior->getGeneralAppearance() !== $oldExterior->getGeneralAppearance()) {
            $description = $description . $prefix . $oldExterior->getGeneralAppearance() . ' => '.$newExterior->getGeneralAppearance();
            $prefix = ', ';
        }

        if ($newExterior->getHeight() !== $oldExterior->getHeight()) {
            $description = $description . $prefix . $oldExterior->getHeight() . ' => '.$newExterior->getHeight();
            $prefix = ', ';
        }

        if ($newExterior->getTorsoLength() !== $oldExterior->getTorsoLength()) {
            $description = $description . $prefix . $oldExterior->getTorsoLength() . ' => '.$newExterior->getTorsoLength();
            $prefix = ', ';
        }

        if ($newExterior->getBreastDepth() !== $oldExterior->getBreastDepth()) {
            $description = $description . $prefix . $oldExterior->getBreastDepth() . ' => '.$newExterior->getBreastDepth();
            $prefix = ', ';
        }

        if ($newExterior->getMarkings() !== $oldExterior->getMarkings()) {
            $description = $description . $prefix . $oldExterior->getMarkings() . ' => '.$newExterior->getMarkings();
            $prefix = ', ';
        }

        if ($newExterior->getInspectorFullName() !== $oldExterior->getInspectorFullName()) {
            $description = $description . $prefix . $oldExterior->getInspectorFullName('*leeg*') . ' => '.$newExterior->getInspectorFullName('*leeg*');
            $prefix = ', ';
        }


        $log = new ActionLog($client, $actionBy, UserActionType::EDIT_EXTERIOR, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }



    /**
     * @param ObjectManager $om
     * @param Client $client
     * @param Employee $actionBy
     * @param Exterior $exterior
     * @return ActionLog
     * @throws \Exception
     */
    public static function deactivateExterior(ObjectManager $om, $client, $actionBy, $exterior)
    {
        if ($exterior->getAnimal() === null) { throw new \Exception('Exterior must have an Animal. Exterior id: ',$exterior->getId()); }

        $inspectorName = $exterior->getInspector() !== null ? self::INSPECTOR_MID_KEY . $exterior->getInspector()->getFullName() : '';

        $description = $exterior->getAnimal()->getUln() . ' '. $exterior->getMeasurementDate()->format(self::DATE_FORMAT) . ' '
            . $exterior->getKind()
            . ', KOP ' . $exterior->getSkull()
            . ', ONT ' . $exterior->getProgress()
            . ', BES ' . $exterior->getMuscularity()
            . ', EVE ' . $exterior->getProportion()
            . ', TYP ' . $exterior->getExteriorType()
            . ', BEE ' . $exterior->getLegWork()
            . ', VAC ' . $exterior->getFur()
            . ', ALG ' . $exterior->getGeneralAppearance()
            . ', SHT ' . $exterior->getHeight()
            . ', LGT ' . $exterior->getTorsoLength()
            . ', BDP ' . $exterior->getBreastDepth()
            . ', MRK ' . $exterior->getMarkings()
            . $inspectorName
        ;

        $log = new ActionLog($client, $actionBy, UserActionType::DEACTIVATE_EXTERIOR, true, $description, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($om, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param Person $actionBy
     * @param Animal $animal
     * @param $content
     * @return ActionLog
     */
    public static function createAnimal(EntityManagerInterface $em, Person $actionBy, Animal $animal, $content): ActionLog
    {
        switch ($animal->getGender()) {
            case GenderType::MALE: $gender = 'Ram'; break;
            case GenderType::FEMALE: $gender = 'Ooi'; break;
            default: $gender = 'Onbekend'; break;
        }

        $message = 'animal[id: '.$animal->getId().', uln: '.$animal->getUln().']: '
            . $gender
            . ', ' . ($animal->getIsAlive() ? 'Levend' : 'Dood')
            . ', Geboortedatum '. $animal->getDateOfBirthString()
            . ', base64content: ' . base64_encode($content)
        ;

        $log = new ActionLog(
            null, $actionBy,
            UserActionType::ADMIN_ANIMAL_CREATE,
            true, $message, self::IS_USER_ENVIRONMENT);
        DoctrineUtil::persistAndFlush($em, $log);

        return $log;
    }


    /**
     * @param EntityManagerInterface $em
     * @param $actionBy
     * @param array $changes
     */
    public static function createAnimalResidence(EntityManagerInterface $em, $actionBy, array $changes = []): void
    {
        if (empty($changes)) {
            return;
        }
        $description = AdminActionLogWriter::parseBatchCreateChangesAsDescription($changes);
        $log = new ActionLog(null, $actionBy, UserActionType::ANIMAL_RESIDENCE_CREATE, true, $description, self::IS_USER_ENVIRONMENT);
        $em->persist($log);
    }

    /**
     * @param EntityManagerInterface $em
     * @param $actionBy
     * @param array $changes
     */
    public static function editAnimalResidence(EntityManagerInterface $em, $actionBy, array $changes = []): void
    {
        if (empty($changes)) {
            return;
        }
        $description = AdminActionLogWriter::parseEditChangesAsDescription($changes);
        $log = new ActionLog(null, $actionBy, UserActionType::ANIMAL_RESIDENCE_EDIT, true, $description, self::IS_USER_ENVIRONMENT);
        $em->persist($log);
    }

    /**
     * @param EntityManagerInterface $em
     * @param $actionBy
     * @param AnimalResidence $animalResidence
     */
    public static function deleteAnimalResidence(EntityManagerInterface $em, $actionBy, AnimalResidence $animalResidence): void
    {
        $description =
            ($animalResidence->getAnimal() ? ReportLabel::ULN.': ' . $animalResidence->getAnimal()->getUln() : 'Empty Animal') .
            ($animalResidence->getLocation() ? ', '.ReportLabel::UBN.': ' . $animalResidence->getLocation()->getUbn() : '').
            ($animalResidence->getCountry() ? ', '.ReportLabel::COUNTRY.': ' . $animalResidence->getCountry() . ' ' : '') .
            ($animalResidence->getStartDate() ? ', '.ReportLabel::START.': '. $animalResidence->getStartDate()->format(DateUtil::DATE_STRING_FORMAT_FILENAME) : '') .
            ($animalResidence->getEndDate() ?  ', '.ReportLabel::END.': '.$animalResidence->getEndDate()->format(DateUtil::DATE_STRING_FORMAT_FILENAME) : '')
        ;
        $log = new ActionLog(null, $actionBy, UserActionType::ANIMAL_RESIDENCE_DELETE, true, $description, self::IS_USER_ENVIRONMENT);
        $em->persist($log);
    }


    /**
     * @param array $batchChanges
     * @return string
     */
    public static function parseBatchCreateChangesAsDescription(array $batchChanges = []): string
    {
        $logDescription = '';
        if (empty($batchChanges) || !is_array($batchChanges)) {
            return $logDescription;
        }

        $setSeparator = '';
        foreach ($batchChanges as $changeSet) {
            $logDescription .= $setSeparator . self::parseCreateChangesAsDescription($changeSet);
            $setSeparator = '; ';
        }

        return $logDescription;
    }


    /**
     * @param array $changes
     * @return string
     */
    public static function parseCreateChangesAsDescription(array $changes = []): string
    {
        $logDescription = '';
        if (empty($changes) || !is_array($changes)) {
            return $logDescription;
        }

        $changeSeparator = '';
        foreach ($changes as $key => $value) {
            $logDescription .= $changeSeparator . $key . ':'.self::formatLogChangeValue($value);
            $changeSeparator = ', ';
        }

        return $logDescription;
    }


    /**
     * @param array $changes
     * @return string
     */
    public static function parseEditChangesAsDescription(array $changes = []): string
    {
        $logDescription = '';
        if (empty($changes) || !is_array($changes)) {
            return $logDescription;
        }

        $changeSeparator = '';
        foreach ($changes as $key => $change) {
            $oldValue = self::formatLogChangeValue($change[0]);
            $newValue = self::formatLogChangeValue($change[1]);
            $logDescription .= $changeSeparator . $key. ': '. $oldValue . ' => '.$newValue;
            $changeSeparator = ', ';
        }

        return $logDescription;
    }


    /**
     * @param string|\DateTime|null $value
     * @return string
     */
    private static function formatLogChangeValue($value): string
    {
        $returnValue = $value;
        if ($value === null) {
            $returnValue = self::NULL_REPLACEMENT_TEXT;
        } elseif ($value instanceof \DateTime) {
            $returnValue = $value->format(DateUtil::DATE_STRING_FORMAT_FILENAME);
        } elseif (is_bool($value)) {
            $returnValue = StringUtil::getBooleanAsString($value);
        }
        return $returnValue;
    }
}
