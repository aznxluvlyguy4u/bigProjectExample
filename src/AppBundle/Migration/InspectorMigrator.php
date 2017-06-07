<?php


namespace AppBundle\Migration;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class InspectorMigrator
{
    const INSPECTOR_CODE_PREFIX = 'NSFO';

    /**
     * @param Connection $conn
     * @param array $csv
     * @return int
     */
    public static function fixInspectorNames(Connection $conn, $csv)
    {
        $namesSearchArray = self::createCorrectedNamesSearchArray($csv);

        $sql = "SELECT i.id, last_name FROM inspector i
                  INNER JOIN person p ON i.id = p.id
                  WHERE first_name ISNULL OR first_name = '' OR first_name = ' '
                ORDER BY last_name, first_name ASC ";
        $results = $conn->query($sql)->fetchAll();

        $totalCount = count($results);
        if($totalCount == 0) { return 0; }

        $updateCount = 0;

        foreach ($results as $result) {
            $id = $result['id'];
            $lastName = $result['last_name'];

            $newFirstName = null;
            $newLastName = null;
            
            $newNamesArray = ArrayUtil::get($lastName, $namesSearchArray);
            if(is_array($namesSearchArray)) {
                $newFirstName = $newNamesArray[JsonInputConstant::FIRST_NAME];
                $newLastName = $newNamesArray[JsonInputConstant::LAST_NAME];
            }

            if($newLastName != null && $newFirstName != null) {
                $sql = "UPDATE person SET first_name = '".$newFirstName."', last_name = '".$newLastName."'
                        WHERE id = ".$id;
                $conn->exec($sql);
                $updateCount++;
            }
        }

        return $updateCount;
    }


    /**
     * @param array $csv
     * @return array
     */
    public static function createCorrectedNamesSearchArray($csv)
    {
        $searchArray = [];
        foreach ($csv as $row) {
            $fullname = $row[0];
            $firstName = $row[1];
            $lastName = $row[2];
            $searchArray[$fullname] = [
              JsonInputConstant::FIRST_NAME => $firstName,
              JsonInputConstant::LAST_NAME => $lastName,
            ];
        }
        return $searchArray;
    }


    /**
     * @param Connection $conn
     * @param InspectorRepository $inspectorRepository
     * @param $csv
     * @return int
     */
    public static function addMissingInspectors(Connection $conn, $inspectorRepository, $csv)
    {
        $newInspectorCount = 0;

        foreach ($csv as $row) {
            $firstName = $row[0];
            $lastName = $row[1];
            $newInspectorCount += self::addMissingInspector($conn, $inspectorRepository, $firstName, $lastName);
        }

        return $newInspectorCount;
    }


    /**
     * Return number of new inspectors added.
     * @param Connection $conn
     * @param InspectorRepository $inspectorRepository
     * @param string $firstName
     * @param string $lastName
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function addMissingInspector(Connection $conn, $inspectorRepository, $firstName, $lastName)
    {
        $sql = "SELECT COUNT(*) FROM inspector i
                  INNER JOIN person p ON i.id = p.id
                WHERE first_name = '".$firstName."' AND last_name = '".$lastName."'";
        $count = $conn->query($sql)->fetch()['count'];

        if($count == 0) {
            $inspectorRepository->insertNewInspector($firstName, $lastName);
            return 1;
        }
        return 0;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @param InspectorRepository $inspectorRepository
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function fixDuplicateInspectors(Connection $conn, CommandUtil $cmdUtil, InspectorRepository $inspectorRepository)
    {
        $sql = "SELECT x.id, x.first_name, x.last_name FROM person x
                INNER JOIN (
                    SELECT p.first_name, p.last_name, p.type FROM inspector i
                      INNER JOIN person p ON i.id = p.id
                      WHERE p.type = 'Inspector'
                    GROUP BY p.first_name, p.last_name, p.type HAVING COUNT(*) > 1
                    )y ON y.first_name = x.first_name AND y.last_name = x.last_name AND y.type = x.type";
        $results =$conn->query($sql)->fetchAll();

        $groupedSearchArray = [];
        foreach ($results as $result) {
            $id = $result['id'];
            $firstName = $result['first_name'];
            $lastName = $result['last_name'];
            $searchKey = $firstName.'__'.$lastName;

            if(array_key_exists($searchKey, $groupedSearchArray)) {
                $group = $groupedSearchArray[$searchKey];
            } else {
                $group = [];
            }

            $group[] = $result;
            $groupedSearchArray[$searchKey] = $group;
        }

        $totalDuplicateCount = count($groupedSearchArray);
        if($totalDuplicateCount == 0) {
            $cmdUtil->writeln('No duplicate inspectors!');
            return;
        }

        $cmdUtil->setStartTimeAndPrintIt($totalDuplicateCount, 1);

        foreach ($groupedSearchArray as $group) {
            $firstInspectorResult = $group[0];
            $primaryInspectorId = $firstInspectorResult['id'];
            foreach ($group as $result) {
                $secondaryInspectorId = $result['id'];
                if($primaryInspectorId != $secondaryInspectorId) {
                    $sql = "UPDATE measurement SET inspector_id = ".$primaryInspectorId." WHERE inspector_id = ".$secondaryInspectorId;
                    $conn->exec($sql);

                    $sql = "INSERT INTO data_import_string_replacement (id, primary_string, secondary_string, type) VALUES (nextval('data_import_string_replacement_id_seq'),'" .$primaryInspectorId. "','" . $secondaryInspectorId . "','Inspector')";
                    $conn->exec($sql);

                    $inspectorRepository->deleteInspector($secondaryInspectorId);
                }
            }
            $cmdUtil->advanceProgressBar(1, 'Removing duplicate inspectors');
        }
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param ObjectManager $em
     * @param array $csv
     * @param $admin
     * @param CommandUtil $cmdUtil
     */
    public static function authorizeInspectorsForExteriorMeasurementsTexelaar(ObjectManager $em, CommandUtil $cmdUtil, array $csv, $admin)
    {
        self::authorizeInspectorsForExteriorMeasurements($em, $cmdUtil, $csv, $admin, PedigreeAbbreviation::NTS);
    }


    /**
     * @param ObjectManager $em
     * @param array $csv
     * @param $admin
     * @param CommandUtil $cmdUtil
     */
    public static function authorizeInspectorsForExteriorMeasurementsBdm(ObjectManager $em, CommandUtil $cmdUtil, array $csv, $admin)
    {
        self::authorizeInspectorsForExteriorMeasurements($em, $cmdUtil, $csv, $admin, PedigreeAbbreviation::BdM);
    }


    /**
     * @param ObjectManager $em
     * @param array $csv
     * @param $admin
     * @param CommandUtil $cmdUtil
     * @param string $pedigreeAbbreviation
     */
    private static function authorizeInspectorsForExteriorMeasurements(ObjectManager $em, CommandUtil $cmdUtil, array $csv, $admin, $pedigreeAbbreviation)
    {
        if(is_int($admin)) {
            /** @var EmployeeRepository $employeeRepository */
            $employeeRepository = $em->getRepository(Employee::class);

            /** @var Employee $admin */
            $admin = $employeeRepository->find($admin);
        }

        switch ($pedigreeAbbreviation) {
            case PedigreeAbbreviation::BdM:
                self::removeExteriorAuthorizations($em, $cmdUtil, $pedigreeAbbreviation, $csv);
                break;
            case PedigreeAbbreviation::NTS:
                self::removeExteriorAuthorizations($em, $cmdUtil, PedigreeAbbreviation::NTS, $csv);
                self::removeExteriorAuthorizations($em, $cmdUtil, PedigreeAbbreviation::TSNH, $csv);
                break;
        }

        $cmdUtil->setStartTimeAndPrintIt(count($csv) * 2, 1, 'Authorize inspectors for '.$pedigreeAbbreviation);

        $authorizations = 0;
        $inspectorCount = 0;
        foreach ($csv as $row) {
            $firstName = $row[0];
            $lastName = $row[1];

            switch ($pedigreeAbbreviation) {
                case PedigreeAbbreviation::BdM:
                    $authorizations += self::authorizeInspectorForExteriorMeasurementsBdM($em, $admin, $firstName, $lastName);
                    break;
                case PedigreeAbbreviation::NTS:
                    $authorizations += self::authorizeInspectorForExteriorMeasurementsTexelaar($em, $admin, $firstName, $lastName);
                    break;
            }
            $inspectorCount++;
            $cmdUtil->advanceProgressBar(1, 'NewAuthorizations|InspectorsChecked: '.$authorizations.'|'.$inspectorCount);
        }
        $em->flush();
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param ObjectManager $em
     * @param Employee $admin
     * @param string $firstName
     * @param string $lastName
     * @return int
     */
    private static function authorizeInspectorForExteriorMeasurementsBdM(ObjectManager $em, Employee $admin, $firstName, $lastName)
    {
        /** @var PedigreeRegisterRepository $inspectorAuthorizationRepository */
        $pedigreeRegisterRepository = $em->getRepository(PedigreeRegister::class);

        $pedigreeRegisterBdM = $pedigreeRegisterRepository->findOneBy(['abbreviation' => PedigreeAbbreviation::BdM]);

        /** @var InspectorRepository $inspectorRepository */
        $inspectorRepository = $em->getRepository(Inspector::class);

        $count = 0;
        /** @var Inspector $inspector */
        $inspector = $inspectorRepository->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if($inspector != null) {
            $count += self::authorizeInspector($em, $admin, $inspector, InspectorMeasurementType::EXTERIOR, $pedigreeRegisterBdM);
            return $count;
        }
        return $count;
    }


    /**
     * @param ObjectManager $em
     * @param Employee $admin
     * @param string $firstName
     * @param string $lastName
     * @return int
     */
    private static function authorizeInspectorForExteriorMeasurementsTexelaar(ObjectManager $em, Employee $admin, $firstName, $lastName)
    {
        /** @var PedigreeRegisterRepository $inspectorAuthorizationRepository */
        $pedigreeRegisterRepository = $em->getRepository(PedigreeRegister::class);

        $pedigreeRegisterTexelaarNTS = $pedigreeRegisterRepository->findOneBy(['abbreviation' => PedigreeAbbreviation::NTS]);
        $pedigreeRegisterTexelaarTSNH = $pedigreeRegisterRepository->findOneBy(['abbreviation' => PedigreeAbbreviation::TSNH]);

        /** @var InspectorRepository $inspectorRepository */
        $inspectorRepository = $em->getRepository(Inspector::class);

        $count = 0;
        /** @var Inspector $inspector */
        $inspector = $inspectorRepository->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if($inspector != null) {
            $count += self::authorizeInspector($em, $admin, $inspector, InspectorMeasurementType::EXTERIOR, $pedigreeRegisterTexelaarNTS);
            $count += self::authorizeInspector($em, $admin, $inspector, InspectorMeasurementType::EXTERIOR, $pedigreeRegisterTexelaarTSNH);
            return $count;
        }
        return $count;
    }


    /**
     * @param ObjectManager $em
     * @param Employee $admin
     * @param Inspector $inspector
     * @param string $measurementType
     * @param PedigreeRegister $pedigreeRegister
     * @return int
     */
    private static function authorizeInspector(ObjectManager $em, Employee $admin, Inspector $inspector, $measurementType, PedigreeRegister $pedigreeRegister = null)
    {
        /** @var InspectorAuthorizationRepository $inspectorAuthorizationRepository */
        $inspectorAuthorizationRepository = $em->getRepository(InspectorAuthorization::class);

        $inspectorAuthorization = $inspectorAuthorizationRepository->findOneBy(
            ['inspector' => $inspector, 'measurementType' => $measurementType, 'pedigreeRegister' => $pedigreeRegister]);

        if($inspectorAuthorization == null) {
            $inspectorAuthorization = new InspectorAuthorization(
                $inspector, $admin, $measurementType, $pedigreeRegister);
            $em->persist($inspectorAuthorization);
            return 1;
        }
        return 0;
    }


    /**
     * @param ObjectManager $em
     * @param CommandUtil $cmdUtil
     * @param $pedigreeAbbreviation
     * @param $csv
     * @return int
     */
    private static function removeExteriorAuthorizations(ObjectManager $em, CommandUtil $cmdUtil, $pedigreeAbbreviation, $csv)
    {
        $fullNames = [];
        foreach ($csv as $row) {
            $firstName = $row[0];
            $lastName = $row[1];
            $fullName = trim($firstName . ' ' . $lastName);
            $fullNames[$fullName] = $fullName;
        }

        /** @var PedigreeRegisterRepository $inspectorAuthorizationRepository */
        $pedigreeRegisterRepository = $em->getRepository(PedigreeRegister::class);
        $pedigreeRegister = $pedigreeRegisterRepository->findOneBy(['abbreviation' => $pedigreeAbbreviation]);

        /** @var InspectorAuthorizationRepository $inspectorAuthorizationRepository */
        $inspectorAuthorizationRepository = $em->getRepository(InspectorAuthorization::class);

        $currentAuthorizations = $inspectorAuthorizationRepository->findBy(['pedigreeRegister' => $pedigreeRegister, 'measurementType' => InspectorMeasurementType::EXTERIOR]);

        $removeCount= 0;
        /** @var InspectorAuthorization $currentAuthorization */
        foreach ($currentAuthorizations as $currentAuthorization)
        {
            $inspector = $currentAuthorization->getInspector();
            $fullNameCurrentInspector = $inspector->getFullName();

            if(!key_exists($fullNameCurrentInspector, $fullNames)) {
                $em->remove($currentAuthorization);
                $removeCount++;
            }
        }
        $em->flush();
        $cmdUtil->writeln('=================');
        $cmdUtil->writeln($removeCount . ' InspectorAuthorizations deleted for Exterior/'.$pedigreeAbbreviation);
        $cmdUtil->writeln('=================');
        return $removeCount;
    }


    /**
     * @param Connection $conn
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function generateInspectorCodes(Connection $conn)
    {
        $sql = "SELECT id, RANK() OVER (ORDER BY id ASC) AS inspector_ordinal
                FROM inspector WHERE is_authorized_nsfo_inspector AND (inspector_code ISNULL OR inspector_code = '')";
        $inspectorRanksById = $conn->query($sql)->fetchAll();

        if(count($inspectorRanksById) == 0) { return 0; }
        
        $maxInspectorCode = self::findMaxInspectorCode($conn);

        $updateString = '';
        $separator = '';
        foreach ($inspectorRanksById as $inspectorRankById) {
            $inspectorId = $inspectorRankById['id'];
            $rank = $inspectorRankById['inspector_ordinal'];
            $updateString = $updateString.$separator.'('.$inspectorId.",'".self::buildInspectorCode($rank, $maxInspectorCode)."')";
            $separator = ',';
        }
        
        $sql = "UPDATE inspector SET inspector_code = v.inspector_code
                FROM (
                  VALUES ".$updateString."
                     ) AS v(id, inspector_code) WHERE inspector.id = v.id";
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return string
     */
    public static function getNewInspectorCode(Connection $conn)
    {
        return self::buildInspectorCode(1, self::findMaxInspectorCode($conn));
    }


    /**
     * @param Connection $conn
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function findMaxInspectorCode(Connection $conn)
    {
        $sql = "SELECT coalesce(
                    MAX(CAST(substr(inspector_code, length(inspector_code)-2, length(inspector_code)) AS INTEGER)), 
                    0) as max
                FROM inspector WHERE (inspector_code NOTNULL AND inspector_code <> '')";
        return $conn->query($sql)->fetch()['max'];
    }


    /**
     * @param int $emptyRank
     * @param int $currentMaxOrdinal
     * @return string
     */
    public static function buildInspectorCode($emptyRank, $currentMaxOrdinal)
    {
        $inspectorCodeOrdinal = str_pad($emptyRank + $currentMaxOrdinal, 3, 0, STR_PAD_LEFT);
        return self::INSPECTOR_CODE_PREFIX.$inspectorCodeOrdinal;
    }


    /**
     * @param Connection $conn
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public static function setIsAuthorizedNsfoInspectorByNTSAuthorization(Connection $conn, CommandUtil $cmdUtil)
    {
        $nts = "'".PedigreeAbbreviation::NTS."'";
        $tsnh = "'".PedigreeAbbreviation::TSNH."'";

        $sql = "UPDATE inspector SET is_authorized_nsfo_inspector = TRUE, inspector_code = NULL
                WHERE id IN (
                  SELECT i.id
                  FROM inspector i
                    INNER JOIN person p ON p.id = i.id
                    INNER JOIN inspector_authorization auth ON i.id = auth.inspector_id
                    INNER JOIN pedigree_register r ON r.id = auth.pedigree_register_id
                  WHERE r.abbreviation = $nts AND i.is_authorized_nsfo_inspector = FALSE
                )";
        $newAuthorizationCount = SqlUtil::updateWithCount($conn, $sql);

        $sql = "UPDATE inspector SET is_authorized_nsfo_inspector = FALSE, inspector_code = NULL
                WHERE id IN (
                  SELECT i.id
                  FROM inspector i
                    INNER JOIN person p ON p.id = i.id
                    LEFT JOIN inspector_authorization auth ON i.id = auth.inspector_id
                    LEFT JOIN pedigree_register r ON r.id = auth.pedigree_register_id
                  WHERE
                    ((r.abbreviation <> $nts AND r.abbreviation <> $tsnh) OR r.abbreviation ISNULL)
                    AND i.is_authorized_nsfo_inspector = TRUE
                )";
        $removedAuthorizationCount = SqlUtil::updateWithCount($conn, $sql);

        $cmdUtil->writeln('InspectorAuthorizations new|removed: '.$newAuthorizationCount.'|'.$removedAuthorizationCount);

        return $removedAuthorizationCount + $newAuthorizationCount;
    }
}