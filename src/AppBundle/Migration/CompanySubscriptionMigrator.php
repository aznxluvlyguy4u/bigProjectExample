<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class CompanySubscriptionMigrator extends MigratorBase
{
    /** @var string */
    private $outputFileName;

    /**
     * CompanySubscriptionMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     * @param string $rootDir
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, $rootDir)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data, $rootDir);

        $this->outputFileName = $rootDir.self::MIGRATION_OUTPUT_FOLDER.'/companies_without_subscription_date.csv';
    }

    public function migrate()
    {
        $this->output->writeln('Creating searchArrays');

        $sql = "SELECT c.id, c.subscription_date, l.ubn FROM company c
                INNER JOIN location l ON l.company_id = c.id
                WHERE c.is_active = TRUE AND l.is_active = TRUE";
        $results = $this->em->getConnection()->query($sql)->fetchAll();

        $companyIdByUbn = [];
        $currentUbnsHavingCompanySubscriptionDates = [];
        foreach ($results as $result) {
            $id = $result['id'];
            $subscriptionDate = $result['subscription_date'];
            $ubn = $result['ubn'];

            $companyIdByUbn[$ubn] = $id;
            if($subscriptionDate != null) {
                $currentUbnsHavingCompanySubscriptionDates[$ubn] = $ubn;
            }
        }

        $latestUbnSubscriptionDatesInCsv = [];
        foreach ($this->data as $record) {

            $startDateString = $record[2];
            if($startDateString == null) { continue; }

            $startDate = new \DateTime($startDateString);
            $ubn = $record[8];

            if(array_key_exists($ubn, $latestUbnSubscriptionDatesInCsv)) {
                $currentStartDate = $latestUbnSubscriptionDatesInCsv[$ubn];
                if($currentStartDate < $startDate) {
                    $latestUbnSubscriptionDatesInCsv[$ubn] = $startDate;
                }
            } else {
                $latestUbnSubscriptionDatesInCsv[$ubn] = $startDate;
            }
        }


        $newCount = 0;
        $ubns = array_keys($latestUbnSubscriptionDatesInCsv);
        $this->cmdUtil->setStartTimeAndPrintIt(count($ubns), 1);
        foreach ($ubns as $ubn) {

            if (!array_key_exists($ubn, $currentUbnsHavingCompanySubscriptionDates) && array_key_exists($ubn, $companyIdByUbn)) {

                $id = $companyIdByUbn[$ubn];
                /** @var \DateTime $latestUbnSubscriptionDateInCsv */
                $latestUbnSubscriptionDateInCsv = $latestUbnSubscriptionDatesInCsv[$ubn];
                $startDateString = $latestUbnSubscriptionDateInCsv->format(SqlUtil::DATE_FORMAT);
                
                $sql = "UPDATE company SET subscription_date = '".$startDateString."' WHERE id = ".$id;
                $this->conn->exec($sql);
                $newCount++;
            }
            $this->cmdUtil->advanceProgressBar(1, $newCount.' new subscriptionDates');
        }
        $this->cmdUtil->setProgressBarMessage($newCount.' new subscriptionDates persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function printOutCsvOfCompaniesWithoutSubscriptionDate()
    {
        $sql = "SELECT c.company_name, a.city, TRIM(CONCAT(p.first_name,' ',p.last_name)) as owner, l.ubn FROM company c
                  LEFT JOIN (
                    SELECT company_id, max(ubn) as ubn FROM location n
                      WHERE n.is_active = TRUE
                    GROUP BY company_id
                  )l ON l.company_id = c.id
                  LEFT JOIN address a ON a.id = c.address_id
                  INNER JOIN person p ON p.id = c.owner_id
                WHERE c.subscription_date ISNULL AND c.is_active = TRUE
                ORDER BY company_name";
        $results = $this->conn->query($sql)->fetchAll();

        if(count($results) > 0) {

            file_put_contents($this->outputFileName,
                'bedrijfsnaam;plaats;primaire contactpersoon;UBN;'
                ."\n", FILE_APPEND);

            foreach ($results as $result) {
                $companyName = $result['company_name'];
                $city = $result['city'];
                $owner = $result['owner'];
                $ubn = $result['ubn'];

                file_put_contents($this->outputFileName,
                    $companyName.';'.$city.';'.$owner.';'.$ubn.';'
                    ."\n", FILE_APPEND);
            }
            $this->output->writeln('Active companies without a subscriptionDate have been printed out');
        }
    }
}