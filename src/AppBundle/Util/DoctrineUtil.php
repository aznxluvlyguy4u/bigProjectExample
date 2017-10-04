<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Employee;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctrineUtil
{

    /**
     * @param ObjectManager $om
     * @param $object
     * @return mixed
     */
    public static function persistAndFlush(ObjectManager $om, $object)
    {
        $om->persist($object);
        $om->flush();

        return $object;
    }

    /**
     * @param ObjectManager $om
     */
    public static function flushClearAndGarbageCollect(ObjectManager $om)
    {
        $om->flush();
        $om->clear();
        gc_collect_cycles();
    }


    /**
     * @param ObjectManager $em
     * @param $lastName
     * @return Employee
     */
    public static function getDeveloper(ObjectManager $em, $lastName = null)
    {
        $employeeRepository = $em->getRepository(Employee::class);
        $criteria = [];
        if(is_string($lastName)) {
            $criteria = ['lastName' => $lastName];
        }

        $developers = $employeeRepository->findBy($criteria, ['id' => 'ASC'], 1);
        if(count($developers) == 1) {
            return $developers[0];
        }
        return null;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil|OutputInterface $cmdUtilOrOutput
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function printDeveloperLastNamesInDatabase(Connection $conn, $cmdUtilOrOutput)
    {
        $sql = "SELECT last_name FROM person p
                INNER JOIN employee e ON e.id = p.id
                WHERE e.access_level = 'DEVELOPER'";
        $results = $conn->query($sql)->fetchAll();

        if(count($results) == 0) {
            $cmdUtilOrOutput->writeln('There are no developers in this database');
        }

        $cmdUtilOrOutput->writeln('Developer lastNames in the database:');
        foreach ($results as $result) {
            $cmdUtilOrOutput->writeln($result['last_name']);
        }
    }


    /**
     * @param EntityManagerInterface|DoctrineUtil $em
     * @return string
     */
    public static function getDatabaseHostAndNameString(EntityManagerInterface $em)
    {
        /** @var Connection $connection */
        $connection = $em->getConnection();
        $databaseName = $connection->getDatabase();
        $host = $connection->getHost();
        $driverName = $connection->getDriver()->getName();
        $port = $connection->getPort();
        $username = $connection->getUsername();

        $sqlCount = "SELECT COUNT(*) FROM pg_stat_activity WHERE pg_stat_activity.datname = '$databaseName'";
        $sqlTotalCount = "SELECT COUNT(*) FROM pg_stat_activity";
        $databaseConnections = $connection->query($sqlCount)->fetch()['count'];
        $totalDatabaseConnections = $connection->query($sqlTotalCount)->fetch()['count'];

        return 'Database: '.$databaseName.' on host:port '.$host.':'.$port.' | username: '.$username
            .' | driverName: '.$driverName.' | connections '.$databaseConnections.'/'.$totalDatabaseConnections;
    }


    /**
     * @param CommandUtil|OutputInterface $cmdUtilOrOutputInterface
     * @param Animal $animal
     * @param string $header
     */
    public static function printAnimalData($cmdUtilOrOutputInterface, Animal $animal, $header = '-- Following animal found --')
    {
        if(!($cmdUtilOrOutputInterface instanceof CommandUtil || $cmdUtilOrOutputInterface instanceof OutputInterface)) {
            return;
        }

        if($animal == null) { $cmdUtilOrOutputInterface->writeln('Animal is empty'); return; }

        if($animal->getIsAlive() === true) {
            $isAliveString = 'true';
        } elseif($animal->getIsAlive() === false) {
            $isAliveString = 'false';
        } else {
            $isAliveString = 'null';
        }

        $cmdUtilOrOutputInterface->writeln([  $header,
            'id: '.$animal->getId(),
            'uln: '.$animal->getUln(),
            'pedigree: '.$animal->getPedigreeCountryCode().$animal->getPedigreeNumber(),
            'aiind/vsmId: '.$animal->getName(),
            'gender: '.$animal->getGender(),
            'isAlive: '.$isAliveString,
            'dateOfBirth: '.$animal->getDateOfBirthString(),
            'dateOfDeath: '.$animal->getDateOfDeathString(),
            'current ubn: '.$animal->getUbn(),
        ]);
    }


    /**
     * @param Connection $conn
     * @param array $tableNamesToUpdate
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateTableSequence(Connection $conn, array $tableNamesToUpdate)
    {
        if(count($tableNamesToUpdate) == 0) { return false; }

        $tableNamesInDb = self::getTableNames($conn);
        $sequenceNames = self::getSequenceNames($conn);

        $incorrectTableNamesOrSequenceNameCount = 0;
        $sequenceUpdatedCount = 0;
        foreach ($tableNamesToUpdate as $tableToUpdate) {
            $tableToUpdate = strtolower($tableToUpdate);

            if(!in_array($tableToUpdate, $tableNamesInDb)) {
                $incorrectTableNamesOrSequenceNameCount++;
                continue;
            }
            
            if(!in_array($tableToUpdate.'_id_seq', $sequenceNames)) {
                $incorrectTableNamesOrSequenceNameCount++;
                continue;
            }

            $sql = "SELECT MAX(id) - (
                      SELECT last_value FROM ".$tableToUpdate."_id_seq
                    ) as max_id_difference FROM ".$tableToUpdate;
            $maxIdDifference = $conn->query($sql)->fetch()['max_id_difference'];

            if($maxIdDifference > 0) {
                SqlUtil::bumpPrimaryKeySeq($conn, $tableToUpdate);
                $sequenceUpdatedCount++;
            }
        }

        return $incorrectTableNamesOrSequenceNameCount == 0;
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getTableNames(Connection $conn)
    {
        $sql = "SELECT tablename FROM pg_catalog.pg_tables
                WHERE tablename NOT LIKE 'pg_%' AND tablename NOT LIKE 'sql_%'
                ORDER BY tablename";
        $results = $conn->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsGroupedBySingleVariable('tablename', $results)['tablename'];
    }


    /**
     * @param Connection $conn
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getSequenceNames(Connection $conn)
    {
        $sql = "SELECT relname FROM pg_class WHERE relkind = 'S'";
        $results = $conn->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsGroupedBySingleVariable('relname', $results)['relname'];
    }


    /**
     * @param CommandUtil $cmdUtil
     * @param EntityManagerInterface $em
     * @param string $question
     * @return Animal|\AppBundle\Entity\Ewe|\AppBundle\Entity\Neuter|\AppBundle\Entity\Ram|null
     */
    public static function askForAnimalByIdOrUln(CommandUtil $cmdUtil, EntityManagerInterface $em, $question = 'Insert id or uln of animal')
    {
        $animalRepository = $em->getRepository(Animal::class);
        $animal = null;

        do {
            $id = $cmdUtil->generateQuestion($question, null);
            if ($id) {
                $animal = $animalRepository->findAnimalByIdOrUln($id);

                if ($animal) {
                    DoctrineUtil::printAnimalData($cmdUtil, $animal, '-- Selected Animal --');

                    if(!$cmdUtil->generateConfirmationQuestion('Is this the correct animal? (y/n, default is no)')){
                        $animal = null;
                    }
                } else {
                    $cmdUtil->writeln('No animal found for given id/uln: '.$id);
                }
            }
        } while (!$animal);

        return $animal;
    }
}