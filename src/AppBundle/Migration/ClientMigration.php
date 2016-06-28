<?php

namespace AppBundle\Migration;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\MigrationStatus;
use AppBundle\Setting\MigrationSetting;
use Doctrine\Common\Collections\ArrayCollection;
use Monolog\Handler\Curl\Util;

/**
 * Class ClientMigration
 * @package AppBundle\Migration
 */
class ClientMigration
{
    /**
     * @param $newClients
     * @param $doctrine
     * @param $encoder
     * @param ArrayCollection $content
     * @return array
     */
    public static function generateNewPasswordsAndEmailsForMigratedClients($newClients, $doctrine, $encoder, ArrayCollection $content)
    {
        $em = $doctrine->getEntityManager();
        $migrationDataRepository = $doctrine->getRepository(Constant::CLIENT_MIGRATION_DATA_REPOSITORY);

        //Settings
        $defaultRunTimeLimitInMinutes = 0; //if set on 0, it will have no run-time-limit
        $runTimeLimitInMinutes = Utils::getValueFromArrayCollectionKeyIfItExists($content, Constant::RUN_TIME_LIMIT_IN_MINUTES, $defaultRunTimeLimitInMinutes);
        set_time_limit($runTimeLimitInMinutes * 60);

        $passwordLength = MigrationSetting::PASSWORD_LENGTH;

        //Initialize counters
        $totalMigrationCount = 0;
        $migrationsWithNewEmailAddress = 0;

        foreach($newClients as $newClient) {

            $emailAddress = $newClient->getEmailAddress();
            $clientHasEmailAddress = $emailAddress != null && $emailAddress != MigrationSetting::EMPTY_EMAIL_ADDRESS_INDICATOR;

            if($clientHasEmailAddress) {
                $newPassword = Utils::randomString($passwordLength);

            } else { //Client has no email address
                $newPassword = MigrationSetting::DEFAULT_MIGRATION_PASSWORD . Utils::randomString(3)
                    . "-" . Utils::randomString(2) . "-" . Utils::randomString(3);

                $emailAddress = self::generateNewEmailAddress($newClient);

                $migrationsWithNewEmailAddress++;
            }

            $encryptedPassword = $encoder->encodePassword($newClient, $newPassword);

            //Migration data
            file_put_contents('/tmp/nsfo_passwords.txt', $emailAddress." ".$newPassword, FILE_APPEND);

            //Counters
            $totalMigrationCount++;
        }

        return array("total_clients_migrated" => $totalMigrationCount,
            "migrations_with_new_default_nfso_email_address" => $migrationsWithNewEmailAddress);
    }

    private static function generateNewEmailAddress(Client $newClient)
    {
        $companies = $newClient->getCompanies();
        $location = null; //default value
        $address = null; //default value
        if(sizeof($companies > 0 )) {
            foreach($companies as $company) {
                $locations = $company->getLocations();
                $address = $company->getAddress();
                if(sizeof($locations > 0)) {
                    $location = $locations->get(0); //just return the first location found in any company
                }
            }
        }

        if($location != null) {
            $prefix = $location->getUbn();

        } else {
            $prefix = uniqid('user');
        }

        return $prefix . "@" . MigrationSetting::DEFAULT_EMAIL_DOMAIN;
    }
}