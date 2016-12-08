<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class MyoMaxMigrator extends MigratorBase
{
    /**
     * MyoMaxMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param OutputInterface $outputInterface
     * @param array $data
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, OutputInterface $outputInterface, array $data)
    {
        parent::__construct($cmdUtil, $em, $outputInterface, $data);
    }

    public function migrate()
    {
        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data)+1, 1);
        $this->resetPrimaryVsmIdsBySecondaryVsmId();

        $sql = "SELECT myo_max, name FROM animal WHERE myo_max NOTNULL";
        $results = $this->em->getConnection()->query($sql)->fetchAll();
        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['name']] = $result['myo_max'];
        }

        $newCount = 0;
        foreach ($this->data as $record) {

            $vsmId = $record[0];
            if(array_key_exists($vsmId, $this->primaryVsmIdsBySecondaryVsmId)) {
                $vsmId = $this->primaryVsmIdsBySecondaryVsmId[$vsmId];
            }

            if (!array_key_exists($vsmId, $searchArray)) {
                $myoMax = $record[1];
                $sql = "UPDATE animal SET myo_max = '".$myoMax."' WHERE name = '".$vsmId."'";
                $this->em->getConnection()->exec($sql);
                $newCount++;
            }
            $this->cmdUtil->advanceProgressBar(1);
        }
        $this->cmdUtil->setProgressBarMessage($newCount.' new records persisted');
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }
}