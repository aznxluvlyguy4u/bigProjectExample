<?php


namespace AppBundle\Service\Migration;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Setting\MigrationSetting;
use AppBundle\Util\Finder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class ClientMigrator
 *
 * @ORM\Entity(repositoryClass="AppBundle\Service\Migration")
 * @package AppBundle\Service\Migration
 */
class ClientMigrator extends MigratorServiceBase
{
    /** @var UserPasswordEncoderInterface */
    private $encoder;

    public function __construct(ObjectManager $em, $rootDir, UserPasswordEncoderInterface $encoder)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->encoder = $encoder;
    }

    /**
     * @param ArrayCollection $content
     * @return array
     */
    public function generateNewPasswordsAndEmailsForMigratedClients(ArrayCollection $content)
    {
        //Settings
        $defaultRunTimeLimitInMinutes = 0; //if set on 0, it will have no run-time-limit
        $runTimeLimitInMinutes = Utils::getValueFromArrayCollectionKeyIfItExists($content, Constant::RUN_TIME_LIMIT_IN_MINUTES, $defaultRunTimeLimitInMinutes);
        set_time_limit($runTimeLimitInMinutes * 60);

        $passwordLength = MigrationSetting::PASSWORD_LENGTH;

        //Initialize counters
        $totalMigrationCount = 0;
        $migrationsWithNewEmailAddress = 0;

        $newClients = $this->em->getRepository(Client::class)->getClientsWithoutAPassword();

        /** @var Client $newClient */
        foreach($newClients as $newClient) {

            $emailAddress = $newClient->getEmailAddress();
            $clientHasEmailAddress = $emailAddress != null && $emailAddress != MigrationSetting::EMPTY_EMAIL_ADDRESS_INDICATOR;

            $ubns = Finder::findUbnsOfClient($newClient);
            $clientHasUbn = false;
            if($ubns->count() > 0) {
                $clientHasUbn = true;
                $ubn = $ubns->get(0); //use the first ubn
            }

            if($clientHasEmailAddress && $clientHasUbn) { //client can manage their own account
                $newPassword = Utils::randomString($passwordLength);

            } else { //Client has no email address and/or no ubn
                $newPassword = MigrationSetting::DEFAULT_MIGRATION_PASSWORD;

                $emailAddress = self::generateNewEmailAddress($newClient);
                $newClient->setEmailAddress($emailAddress);

                $migrationsWithNewEmailAddress++;
            }

            $encryptedPassword = $this->encoder->encodePassword($newClient, $newPassword);
            $newClient->setPassword($encryptedPassword);

            $this->em->persist($newClient);
            $this->em->flush();

            //Migration data
            file_put_contents('/tmp/nsfo_passwords.txt', $emailAddress." ".$newPassword."\n", FILE_APPEND);

            //Counters
            $totalMigrationCount++;
        }

        return array("total_clients_migrated" => $totalMigrationCount,
            "migrations_with_new_default_nfso_email_address" => $migrationsWithNewEmailAddress);
    }

    /**
     * @param Client $newClient
     * @return string
     */
    private static function generateNewEmailAddress(Client $newClient)
    {
        $companies = $newClient->getCompanies();
        $location = null; //default value
        $address = null; //default value
        if(sizeof($companies) > 0 ) {
            /** @var Company $company */
            foreach($companies as $company) {
                $locations = $company->getLocations();
                $address = $company->getAddress();
                if(sizeof($locations) > 0) {
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