<?php


namespace AppBundle\Service\Migration;

use AppBundle\Cache\TailLengthCacher;
use AppBundle\Cache\WeightCacher;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\MeasurementsUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class BirthDataMigrator
 */
class BirthDataMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir);
    }

    /** @inheritDoc */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn('====== PRE migration fixes ======');
        $this->data = $this->parseCSV(self::BIRTH);
        MeasurementsUtil::generateAnimalIdAndDateValues($this->conn, false, $cmdUtil);

        $this->writeln('====== Exteriors litters ======');
        $this->migrateBirthWeights();
        $this->migrateTailLengths();
        $this->migrateBirthProgress();

        $this->writeLn('====== POST migration updates ======');
        $this->cmdUtil->writeln( TailLengthCacher::updateAll($this->conn) . ' tailLength cache records updated');
        $this->cmdUtil->writeln( WeightCacher::updateAllBirthWeights($this->conn) . ' birth weight cache records updated');
    }


    private function migrateBirthWeights()
    {
        //TODO
    }


    private function migrateTailLengths()
    {
        //TODO
    }


    private function migrateBirthProgress()
    {
        //TODO
    }
}