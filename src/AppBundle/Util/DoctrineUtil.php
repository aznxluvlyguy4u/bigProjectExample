<?php

namespace AppBundle\Util;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Employee;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use AppBundle\Entity\Tag;
use AppBundle\Entity\Token;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
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
     * @param ObjectManager $em
     * @return string
     */
    public static function getDatabaseHostAndNameString(ObjectManager $em)
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
     * @param ObjectManager $em
     * @return string
     */
    public static function getRandomAdminAccessTokenCode(ObjectManager $em)
    {
        $sql = "SELECT token.code as code 
                FROM employee 
                    INNER JOIN token ON employee.id = token.owner_id
                    INNER JOIN person ON employee.id = person.id
                WHERE token.type = 'ACCESS' AND person.is_active = TRUE";
        $tokenCodes = $em->getConnection()->query($sql)->fetchAll();

        //null check
        if(count($tokenCodes) == 0) {
            return null;
        }

        $choice = rand(1, count($tokenCodes)-1);
        return $tokenCodes[$choice]['code'];
    }


    /**
     * @param ObjectManager $em
     * @param Location|int $locationToSkip
     * @param int $minAliveAnimalsCount
     * @return Location
     */
    public static function getRandomActiveLocation(ObjectManager $em, $locationToSkip = null, $minAliveAnimalsCount = 30)
    {
        if($locationToSkip instanceof Location) {
            $locationFilter = " AND location.id <> ".$locationToSkip->getId();
        } elseif (is_int($locationToSkip)) {
            $locationFilter = " AND location.id <> ".$locationToSkip;
        } else {
            $locationFilter = "";
        }

        $sql = "SELECT location.id as id 
                FROM (location 
                      INNER JOIN (
                                  SELECT location_id, COUNT(*) 
                                  FROM animal 
                                  WHERE animal.transfer_state IS NULL AND animal.is_alive = true 
                                  GROUP BY location_id HAVING COUNT(*) > ".$minAliveAnimalsCount." 
                                  ) 
                                  lc ON location.id = lc.location_id
                      ) 
                      WHERE location.is_active = TRUE".$locationFilter;

        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, Location::class);
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return null|Ram
     */
    public static function getRandomRamFromLocation(ObjectManager $em, Location $location)
    {
        return self::getRandomAnimalFromLocation($em, $location, 'Ram');
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return null|Ewe
     */
    public static function getRandomEweFromLocation(ObjectManager $em, Location $location)
    {
        return self::getRandomAnimalFromLocation($em, $location, 'Ewe');
    }
    

    /**
     * @param ObjectManager $em
     * @param Location $location
     * @param null $gender
     * @return null|Animal|Ram|Ewe|Neuter
     */
    public static function getRandomAnimalFromLocation(ObjectManager $em, Location $location, $gender = null)
    {
        if($gender == 'Ram') {
            $typeFilter =  " AND a.type = 'Ram'";
            $clazz = Ram::class;
        } elseif ($gender == 'Ewe') {
            $typeFilter =  " AND a.type = 'Ewe'";
            $clazz = Ewe::class;
        } elseif ($gender == 'Neuter') {
            $typeFilter =  " AND a.type = 'Neuter'";
            $clazz = Neuter::class;
        } else {
            $typeFilter = null;
            $clazz = Animal::class;
        }

        $sql = "SELECT * FROM animal a WHERE a.location_id = ".$location->getId()." AND is_alive = TRUE AND a.transfer_state IS NULL".$typeFilter;
        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, $clazz);
    }


    /**
     * @param ObjectManager $em
     * @param Location $location
     * @return null|Tag
     */
    public static function getRandomUnassignedTag(ObjectManager $em, Location $location)
    {
        $ownerId = $location->getCompany()->getOwner()->getId();
        $locationId = $location->getId();
        $sql = "SELECT * FROM tag t WHERE t.owner_id = ".$ownerId." AND t.location_id = ".$locationId." AND tag_status = 'UNASSIGNED'";
        $results = $em->getConnection()->query($sql)->fetchAll();
        return self::getRandomItemFromResults($em, $results, Tag::class);
    }
    
    
    /**
     * @param ObjectManager $em
     * @param $results
     * @param $clazz
     * @return null|object
     */
    private static function getRandomItemFromResults(ObjectManager $em, $results, $clazz)
    {
        $resultsSize = count($results);
        //null check
        if($resultsSize == 0) {
            return null;
        }

        $result = null;
        $maximumRetries = 10;

        for($i = 0; $i < $maximumRetries; $i++) {
            $choice = rand(0, $resultsSize-1);
            $result = $em->getRepository($clazz)->find($results[$choice]['id']);

            if ($result != null) {
                return $result;
            }
        }
        return null;
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
}