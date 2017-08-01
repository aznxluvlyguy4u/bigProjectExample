<?php


namespace AppBundle\Service\Migration;

use AppBundle\Cache\ExteriorCacher;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\Measurement;
use AppBundle\Entity\Person;
use AppBundle\Enumerator\QueryType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ExteriorMigrator
 */
class ExteriorMigrator extends Migrator2017JunServiceBase implements IMigratorService
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
        $this->data = $this->parseCSV(self::EXTERIORS);
        $this->createInspectorSearchArrayAndInsertNewInspectors();

        $this->writeln('====== Exteriors litters ======');
        $this->migrateNewExteriors();

        $this->writeLn('====== POST migration updates ======');
        $this->cmdUtil->writeln( ExteriorCacher::updateAllExteriors($this->conn) . ' exterior cache records updated');
    }



    private function createInspectorSearchArrayAndInsertNewInspectors()
    {
        $this->writeLn('Creating inspector search Array ...');

        DoctrineUtil::updateTableSequence($this->conn, [Person::TABLE_NAME]);

        $this->inspectorIdsInDbByFullName = $this->getInspectorSearchArrayWithNameCorrections();

        $newInspectors = [];

        foreach ($this->data as $record) {
            $inspectorFullName = $record[14];

            if ($inspectorFullName !== '' && !key_exists($inspectorFullName, $this->inspectorIdsInDbByFullName)
            && !key_exists($inspectorFullName, $newInspectors)) {
                $newInspectors[$inspectorFullName] = $inspectorFullName;
            }
        }

        if (count($newInspectors) === 0) {
            return;
        }

        $this->writeLn('Inserting '.count($newInspectors).' new inspectors ...');
        foreach ($newInspectors as $newInspectorFullName) {
            $nameParts = explode(' ', $newInspectorFullName);
            $inspector = new Inspector();
            $inspector
                ->setFirstName($nameParts[0])
                ->setLastName($nameParts[1])
                ->setPassword('BLANK')
            ;
            $this->em->persist($inspector);
            $this->writeLn($inspector->getFullName());
        }
        $this->em->flush();

        $this->writeln(count($newInspectors) . ' new inspectors inserted (without inspectorCode nor authorization');
    }


    private function migrateNewExteriors()
    {
        $this->writeLn('=== Migrating NEW exterior measurements ===');

        DoctrineUtil::updateTableSequence($this->conn, [Measurement::TABLE_NAME]);

        $this->sqlBatchProcessor
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
            ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO measurement (id, inspector_id, log_date, measurement_date, 
                                                                          type, animal_id_and_date) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO exterior (id, animal_id, skull, muscularity, proportion, 
                                              exterior_type, leg_work, fur, general_appearance, height, breast_depth, 
                                              torso_length, markings, kind, progress)  VALUES ");

        $maxId = SqlUtil::getMaxId($this->conn, Measurement::TABLE_NAME);
        $firstMaxId = $maxId + 1;

        $this->writeLn('Create animal_id by vsmId search array');
        $this->animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        //TODO

        foreach ($this->data as $record) {
            $vsmId = $record[0];
            $measurementDateString = $record[1];
            $kind = $record[2];
            $skull = $record[3];
            $progress = $record[4];
            $muscularity = $record[5];
            $proportion = $record[6];
            $exteriorType = $record[7];
            $legWork = $record[8];
            $fur = $record[9];
            $generalAppearance = $record[10];
            $height = $record[11];
            $breastDepth = $record[12];
            $torsoLength = $record[13];
            $inspectorFullName = $record[14];

            $inspectorId = $inspectorFullName !== '' ? $this->inspectorIdsInDbByFullName[$inspectorFullName] : null;


            /* Linear measurements
            $KOP_LINEAR = $record[15];
            $HALS = $record[16];
            $HALSAANSLUITING = $record[17];
            $UPSTANDING = $record[18];
            $VOORHAND = $record[19];
            $SCHOUDER = $record[20];
            $BOVENBOUW = $record[21];
            $RUGLENGTE = $record[22];
            $RUGBREEDTE = $record[23];
            $KRUIS = $record[24];
            $KRUIS_HELLING = $record[25];
            $KRUIS_BREEDTE = $record[26];
            $RONDING_BIL = $record[27];
            $STAND_VOORBENEN = $record[28];
            $STAND_ZIJAANZICHT_ACHTERBENEN = $record[29];
            $STAND_ACHTERAANZICHT_ACHTERBENEN = $record[30];
            $PIJP_OMVANG = $record[31];
            $WOL_FIJNHEID = $record[32];
            $WOL_STAPELING = $record[33];
            $INSPECTEUR_LINEAR = $record[34];
            */


            //TODO
        }



    }



}