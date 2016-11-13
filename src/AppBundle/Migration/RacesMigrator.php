<?php


namespace AppBundle\Migration;


use AppBundle\Entity\Employee;
use AppBundle\Entity\Race;
use AppBundle\Enumerator\Specie;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class RacesMigrator extends MigratorBase
{
    /** @var Employee */
    private $developer;

    /**
     * RacesMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     * @param Employee $developer
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data, Employee $developer = null)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
        $this->developer = $developer;
    }
    
    public function migrate()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);

        $sql = "SELECT full_name FROM race";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $fullName = $result['full_name'];
            $searchArray[$fullName] = $fullName;
        }

        $newCount = 0;
        foreach ($this->data as $record) {

            $fullName = utf8_encode($record[2]);

            if(!array_key_exists($fullName, $searchArray)) {
                //$vsmId = $record[0];
                $abbreviation = $record[1];
                $startDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($record[3]);
                $endDate = TimeUtil::getDateTimeFromFlippedAndNullCheckedDateString($record[4]);
                //$createdByString = $record[5];
                //$creationDate = $record[6];
                //$lastUpdatedByString = $record[7];
                //$lastUpdateDate = $record[8];
                $specie = $this->convertSpecieData($record[9]);

                $race = new Race($abbreviation, $fullName);
                $race->setStartDate($startDate);
                $race->setEndDate($endDate);
                $race->setCreatedBy($this->developer);
                $race->setCreationDate(new \DateTime());
                $race->setSpecie($specie);

                $this->em->persist($race);
                $newCount++;
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->em->flush();
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param string $vsmSpecieData
     * @return string
     */
    public function convertSpecieData($vsmSpecieData){
        return strtr($vsmSpecieData, [ 'SC' => Specie::SHEEP, 'GE' => Specie::GOAT]);
    }
}