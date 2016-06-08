<?php

namespace AppBundle\Migration;

use AppBundle\Component\Utils;
use AppBundle\Entity\ClientMigrationData;
use AppBundle\Enumerator\MigrationStatus;
use AppBundle\Setting\MigrationSetting;

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
     * @return array
     */
    public static function generateNewPasswordsAndEmailsForMigratedClients($newClients, $doctrine, $encoder)
    {
        $em = $doctrine->getEntityManager();
        $migrationDataRepository = $doctrine->getRepository('AppBundle:ClientMigrationData');

        //Settings
        $passwordLength = 9;

        //Default value
        $hasDefaultNsfoEmailAddress = false;

        //Initialize counters
        $oldMigrationCount = 0;
        $totalMigrationCount = 0;
        $migrationsWithNewEmailAddress = 0;

        foreach($newClients as $newClient) {
            //First check if client already is in the Migration table
            $migrationData = $migrationDataRepository->getMigrationDataOfClient($newClient);
            $oldMigrationDataExisted = $migrationData != null;
            if($oldMigrationDataExisted) {
                //check if it has a default nsfo email address before removing from database
                $hasDefaultNsfoEmailAddress = $migrationData->getHasDefaultNsfoEmailAddress();

                $oldMigrationCount++;
                $em->remove($migrationData);
                $em->flush();
            }

            $migrationData = new ClientMigrationData();
            $migrationData->setHasDefaultNsfoEmailAddress($hasDefaultNsfoEmailAddress); //immediately set this value

            $emailAddress = $newClient->getEmailAddress();
            $clientHasEmailAddress = $emailAddress != null && $emailAddress != MigrationSetting::EMPTY_EMAIL_ADDRESS_INDICATOR;

            if($clientHasEmailAddress) {
                $newPassword = Utils::randomString($passwordLength);

            } else { //Client has no email address
                $newPassword = MigrationSetting::DEFAULT_MIGRATION_PASSWORD;

                //TODO Phase2+ update the logic for clients with more than one UBN.
                $ubn = $newClient->getCompanies()->get(0)->getLocations()->get(0)->getUbn();
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
}