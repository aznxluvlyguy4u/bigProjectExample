<?php

namespace AppBundle\Migration;

use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\ClientMigrationData;
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

        $defaultTestRunSetting = false;
        $useTestRunUbns = Utils::getValueFromArrayCollectionKeyIfItExists($content, Constant::TEST_RUN_WITH_DEFAULT_UBNS, $defaultTestRunSetting);

        $passwordLength = MigrationSetting::PASSWORD_LENGTH;

        //Default value
        $hasDefaultNsfoEmailAddress = false;

        //Initialize counters
        $oldMigrationCount = 0;
        $totalMigrationCount = 0;
        $migrationsWithNewEmailAddress = 0;

        foreach($newClients as $newClient) {

            $migrationData = new ClientMigrationData();
            $migrationData->setHasDefaultNsfoEmailAddress($hasDefaultNsfoEmailAddress); //immediately set this value

            $emailAddress = $newClient->getEmailAddress();
            $clientHasEmailAddress = $emailAddress != null && $emailAddress != MigrationSetting::EMPTY_EMAIL_ADDRESS_INDICATOR;

            if($clientHasEmailAddress) {
                $newPassword = Utils::randomString($passwordLength);

            } else { //Client has no email address
                $newPassword = MigrationSetting::DEFAULT_MIGRATION_PASSWORD;

                if($useTestRunUbns) {
                    $ubn = self::useMockUbnIfRealUbnDoesNotExist($newClient);

                } else { //Use actual ubns
                    //TODO Phase2+ update the logic for clients with more than one UBN.
                    $ubn = $newClient->getCompanies()->get(0)->getLocations()->get(0)->getUbn();
                }

                $emailAddress = $ubn . "@" . MigrationSetting::DEFAULT_EMAIL_DOMAIN;
                $newClient->setEmailAddress($emailAddress);
                $migrationData->setHasDefaultNsfoEmailAddress(true);
            }

            $encryptedPassword = $encoder->encodePassword($newClient, $newPassword);
            $newClient->setPassword($encryptedPassword);

            //Migration data
            $migrationData->setClient($newClient);
            $migrationData->setEncryptedPassword($encryptedPassword);
            $migrationData->setUnencryptedPassword($newPassword);
            $migrationData->setMigrationStatus(MigrationStatus::NEW_PASSWORD);

            $em->persist($newClient);
            $em->persist($migrationData);

            $em->flush();

            //Counters
            $totalMigrationCount++;

            if($migrationData->getHasDefaultNsfoEmailAddress()) {
                $migrationsWithNewEmailAddress++;
            }
        }

        return array("total_clients_migrated" => $totalMigrationCount,
            "old_client_migrations_removed" => $oldMigrationCount,
            "migrations_with_new_default_nfso_email_address" => $migrationsWithNewEmailAddress);
    }

    /**
     * @param Client $client
     * @return string
     */
    private static function useMockUbnIfRealUbnDoesNotExist(Client $client)
    {
        $companies = $client->getCompanies();

        if($companies->count() > 0) {
            $locations = $companies->get(0)->getLocations();
            if($locations->count() > 0) {
                $ubn = $locations->get(0)->getUbn();
            } else {
                //just some random number without zeros, so it cannot start with a zero
                $ubn = Utils::randomString(9, '123456789');
            }
        } else {
            $ubn = Utils::randomString(9, '123456789');
        }

        return $ubn;
    }
}