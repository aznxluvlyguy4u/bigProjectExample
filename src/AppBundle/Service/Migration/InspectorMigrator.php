<?php


namespace AppBundle\Service\Migration;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeRepository;
use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorAuthorization;
use AppBundle\Entity\InspectorAuthorizationRepository;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Entity\PedigreeRegister;
use AppBundle\Entity\PedigreeRegisterRepository;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Enumerator\CommandTitle;
use AppBundle\Enumerator\InspectorMeasurementType;
use AppBundle\Enumerator\PedigreeAbbreviation;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

/**
 * Class InspectorMigrator
 */
class InspectorMigrator extends MigratorServiceBase implements IMigratorService
{
    const IMPORT_SUB_FOLDER = 'inspectors/';

    //Inspectors prefixes should be 4 chars length
    const NSFO_INSPECTOR_CODE_PREFIX = 'NSFO';
    const EXTERNAL_INSPECTOR_CODE_PREFIX = 'EXT0';

    CONST NAME_CORRECTIONS = 'finder_name_corrections';
    CONST NEW_NAMES = 'finder_name_new';
    CONST AUTHORIZE_TEXELAAR = 'finder_authorize_texelaar';
    CONST AUTHORIZE_BDM = 'finder_authorize_bdm';


    /** @var InspectorRepository */
    private $inspectorRepository;

    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, self::BATCH_SIZE, self::IMPORT_SUB_FOLDER, $rootDir);
        $this->inspectorRepository = $this->em->getRepository(Inspector::class);

        $this->filenames = array(
            self::NAME_CORRECTIONS => 'inspector_name_corrections.csv',
            self::NEW_NAMES => 'inspector_new_names.csv',
            self::AUTHORIZE_TEXELAAR => 'authorize_inspectors_texelaar.csv',
            self::AUTHORIZE_BDM => 'authorize_inspectors_bdm.csv',
        );
    }

    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn(CommandTitle::INSPECTOR);

        $this->cmdUtil->writeln([DoctrineUtil::getDatabaseHostAndNameString($this->em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Fix inspector names', "\n",
            '2: Add missing inspectors', "\n",
            '3: Fix duplicate inspectors', "\n",
            '4: Authorize inspectors', "\n",
            '5: Set and Remove isAuthorizedNsfoInspector status by NTS authorization', "\n",
            '   (inspectors with updated isAuthorizedNsfoInspector will have inspectorCode set to NULL)', "\n",
            '6: Generate inspectorCodes, if null', "\n",
            'other: EXIT ', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {
            case 1: $this->fixInspectorNames(); break;
            case 2: $this->addMissingInspectors(); break;
            case 3: $this->fixDuplicateInspectors(); break;
            case 4: $this->authorizeTexelaarInspectors(); $this->authorizeBdmInspectors(); break;
            case 5: $this->setIsAuthorizedNsfoInspectorByNTSAuthorization(); break;
            case 6: $this->generateInspectorCodes(); break;
            default: $this->writeln('EXIT'); return;
        }
        $this->run($cmdUtil);
    }


    private function authorizeTexelaarInspectors()
    {
        $csv = $this->parseCSV(self::AUTHORIZE_TEXELAAR);
        $admin = $this->cmdUtil->questionForAdminChoice($this->em, AccessLevelType::SUPER_ADMIN, false);

        self::authorizeInspectorsForExteriorMeasurements($this->em, $this->cmdUtil, $csv, $admin, PedigreeAbbreviation::NTS);
    }


    private function authorizeBdmInspectors()
    {
        $csv = $this->parseCSV(self::AUTHORIZE_BDM);
        $admin = $this->cmdUtil->questionForAdminChoice($this->em, AccessLevelType::SUPER_ADMIN, false);

        self::authorizeInspectorsForExteriorMeasurements($this->em, $this->cmdUtil, $csv, $admin, PedigreeAbbreviation::BdM);
    }


    /**
     * @return int
     */
    private function fixInspectorNames()
    {
        $csv = $this->parseCSV(self::NAME_CORRECTIONS);
        $namesSearchArray = self::createCorrectedNamesSearchArray($csv);

        $sql = "SELECT i.id, last_name FROM inspector i
                  INNER JOIN person p ON i.id = p.id
                  WHERE first_name ISNULL OR first_name = '' OR first_name = ' '
                ORDER BY last_name, first_name ASC ";
        $results = $this->conn->query($sql)->fetchAll();

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
                $this->conn->exec($sql);
                $updateCount++;
            }
        }

        $result = $updateCount == 0 ? 'No inspectors names updated' : $updateCount.' inspector names updated!' ;
        $this->writeln($result);

        return $updateCount;
    }


    /**
     * @param array $csv
     * @return array
     */
    private static function createCorrectedNamesSearchArray($csv)
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
     * @return int
     */
    private function addMissingInspectors()
    {
        $newInspectorCount = 0;

        $csv = $this->parseCSV(self::NEW_NAMES);

        foreach ($csv as $row) {
            $firstName = $row[0];
            $lastName = $row[1];
            $newInspectorCount += self::addMissingInspector($this->conn, $this->inspectorRepository, $firstName, $lastName);
        }

        $result = $newInspectorCount == 0 ? 'No new inspectors added' : $newInspectorCount.' new inspectors added!' ;
        $this->writeln($result);

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
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fixDuplicateInspectors()
    {
        $sql = "SELECT x.id, x.first_name, x.last_name FROM person x
                INNER JOIN (
                    SELECT p.first_name, p.last_name, p.type FROM inspector i
                      INNER JOIN person p ON i.id = p.id
                      WHERE p.type = 'Inspector'
                    GROUP BY p.first_name, p.last_name, p.type HAVING COUNT(*) > 1
                    )y ON y.first_name = x.first_name AND y.last_name = x.last_name AND y.type = x.type";
        $results =$this->conn->query($sql)->fetchAll();

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
            $this->cmdUtil->writeln('No duplicate inspectors!');
            return;
        }

        $this->cmdUtil->setStartTimeAndPrintIt($totalDuplicateCount, 1);

        foreach ($groupedSearchArray as $group) {
            $firstInspectorResult = $group[0];
            $primaryInspectorId = $firstInspectorResult['id'];
            foreach ($group as $result) {
                $secondaryInspectorId = $result['id'];
                if($primaryInspectorId != $secondaryInspectorId) {
                    $sql = "UPDATE measurement SET inspector_id = ".$primaryInspectorId." WHERE inspector_id = ".$secondaryInspectorId;
                    $this->conn->exec($sql);

                    $sql = "INSERT INTO data_import_string_replacement (id, primary_string, secondary_string, type) VALUES (nextval('data_import_string_replacement_id_seq'),'" .$primaryInspectorId. "','" . $secondaryInspectorId . "','Inspector')";
                    $this->conn->exec($sql);

                    $this->inspectorRepository->deleteInspector($secondaryInspectorId);
                }
            }
            $this->cmdUtil->advanceProgressBar(1, 'Removing duplicate inspectors');
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
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
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function generateInspectorCodes()
    {
        $updateCount = self::generateNsfoInspectorCodes();
        $updateCount += self::generateExternalInspectorCodes();

        $result = $updateCount == 0 ? 'No new inspectorCodes added' : $updateCount.' new inspectorCodes added!' ;
        $this->writeln($result);

        return $updateCount;
    }


    /**
     * @return int
     */
    private function generateNsfoInspectorCodes()
    {
        $sql = "SELECT id, RANK() OVER (ORDER BY id ASC) AS inspector_ordinal
                FROM inspector WHERE is_authorized_nsfo_inspector AND (inspector_code ISNULL OR inspector_code = '')";
        $inspectorRanksById = $this->conn->query($sql)->fetchAll();

        if(count($inspectorRanksById) == 0) { return 0; }

        $maxInspectorCode = self::findMaxNsfoInspectorCode($this->conn);

        $updateString = '';
        $separator = '';
        foreach ($inspectorRanksById as $inspectorRankById) {
            $inspectorId = $inspectorRankById['id'];
            $rank = $inspectorRankById['inspector_ordinal'];
            $updateString = $updateString.$separator.'('.$inspectorId.",'".self::buildNsfoInspectorCode($rank, $maxInspectorCode)."')";
            $separator = ',';
        }

        $sql = "UPDATE inspector SET inspector_code = v.inspector_code
                FROM (
                  VALUES ".$updateString."
                     ) AS v(id, inspector_code) WHERE inspector.id = v.id";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * NOTE! Only external inspectors for which the exterior measurements should be included in the MiXBLUP process,
     * should get an inspector code.
     *
     * @return int
     */
    private function generateExternalInspectorCodes()
    {
        $sql = "SELECT i.id, RANK() OVER (ORDER BY i.id ASC) AS inspector_ordinal
                FROM inspector i
                  INNER JOIN inspector_authorization auth ON auth.inspector_id = i.id
                  INNER JOIN pedigree_register r ON r.id = auth.pedigree_register_id
                WHERE i.is_authorized_nsfo_inspector = FALSE AND (i.inspector_code ISNULL OR i.inspector_code = '')
                  AND r.abbreviation = '".PedigreeAbbreviation::BdM."'";
        $inspectorRanksById = $this->conn->query($sql)->fetchAll();

        if(count($inspectorRanksById) == 0) { return 0; }

        $maxInspectorCode = self::findMaxExternalInspectorCode($this->conn);

        $updateString = '';
        $separator = '';
        foreach ($inspectorRanksById as $inspectorRankById) {
            $inspectorId = $inspectorRankById['id'];
            $rank = $inspectorRankById['inspector_ordinal'];
            $updateString = $updateString.$separator.'('.$inspectorId.",'".self::buildExternalInspectorCode($rank, $maxInspectorCode)."')";
            $separator = ',';
        }

        $sql = "UPDATE inspector SET inspector_code = v.inspector_code
                FROM (
                  VALUES ".$updateString."
                     ) AS v(id, inspector_code) WHERE inspector.id = v.id";
        return SqlUtil::updateWithCount($this->conn, $sql);
    }


    /**
     * @param Connection $conn
     * @return string
     */
    public static function getNewNsfoInspectorCode(Connection $conn)
    {
        return self::buildNsfoInspectorCode(1, self::findMaxNsfoInspectorCode($conn));
    }


    /**
     * @param Connection $conn
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function findMaxNsfoInspectorCode(Connection $conn)
    {
        return self::findMaxInspectorCode($conn, true);
    }


    /**
     * @param Connection $conn
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function findMaxExternalInspectorCode(Connection $conn)
    {
        return self::findMaxInspectorCode($conn, false);
    }


    /**
     * @param Connection $conn
     * @param bool $isAuthorizedNsfoInspector
     * @return mixed
     */
    private static function findMaxInspectorCode(Connection $conn, $isAuthorizedNsfoInspector = true)
    {
        $filter = $isAuthorizedNsfoInspector ? '' : ' = FALSE';

        $sql = "SELECT coalesce(
                    MAX(CAST(substr(inspector_code, length(inspector_code)-2, length(inspector_code)) AS INTEGER)), 
                    0) as max
                FROM inspector WHERE (inspector_code NOTNULL AND inspector_code <> '') AND is_authorized_nsfo_inspector".$filter;
        return $conn->query($sql)->fetch()['max'];
    }


    /**
     * @param $emptyRank
     * @param $currentMaxOrdinal
     * @return string
     */
    public static function buildNsfoInspectorCode($emptyRank, $currentMaxOrdinal)
    {
        return self::buildInspectorCode(self::NSFO_INSPECTOR_CODE_PREFIX, $emptyRank, $currentMaxOrdinal);
    }


    /**
     * @param $emptyRank
     * @param $currentMaxOrdinal
     * @return string
     */
    public static function buildExternalInspectorCode($emptyRank, $currentMaxOrdinal)
    {
        return self::buildInspectorCode(self::EXTERNAL_INSPECTOR_CODE_PREFIX, $emptyRank, $currentMaxOrdinal);
    }


    /**
     * @param $prefix
     * @param $emptyRank
     * @param $currentMaxOrdinal
     * @return string
     */
    public static function buildInspectorCode($prefix, $emptyRank, $currentMaxOrdinal)
    {
        $inspectorCodeOrdinal = str_pad($emptyRank + $currentMaxOrdinal, 3, 0, STR_PAD_LEFT);
        return $prefix.$inspectorCodeOrdinal;
    }


    /**
     * @return int
     */
    private function setIsAuthorizedNsfoInspectorByNTSAuthorization()
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
        $newAuthorizationCount = SqlUtil::updateWithCount($this->conn, $sql);

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
        $removedAuthorizationCount = SqlUtil::updateWithCount($this->conn, $sql);

        $this->cmdUtil->writeln('InspectorAuthorizations new|removed: '.$newAuthorizationCount.'|'.$removedAuthorizationCount);

        return $removedAuthorizationCount + $newAuthorizationCount;
    }
}