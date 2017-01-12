<?php

namespace AppBundle\Command;

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
use AppBundle\Migration\InspectorMigrator;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoInspectorCommand extends ContainerAwareCommand
{
    const TITLE = 'Inspectors';
    const DEFAULT_OPTION = 0;
    const ACTION_BY_ADMIN_ID = 2152; //Reinard Everts

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var InspectorRepository */
    private $inspectorRepository;

    /** @var EmployeeRepository */
    private $adminRepository;

    /** @var PedigreeRegisterRepository */
    private $pedigreeRegisterRepository;

    /** @var InspectorAuthorizationRepository */
    private $inspectorAuthorizationRepository;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var OutputInterface */
    private $output;
    
    /** @var Employee */
    private $actionByAdmin;

    protected function configure()
    {
        $this
            ->setName('nsfo:inspector')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ObjectManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);
        $this->output = $output;

        $this->inspectorRepository = $em->getRepository(Inspector::class);
        $this->adminRepository = $em->getRepository(Employee::class);
        $this->pedigreeRegisterRepository = $em->getRepository(PedigreeRegister::class);
        $this->inspectorAuthorizationRepository = $em->getRepository(InspectorAuthorization::class);


        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Fix inspector names', "\n",
            '2: Add missing inspectors', "\n",
            '3: Fix duplicate inspectors', "\n",
            '4: Authorize inspectors', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                $count = $this->fixInspectorNames();
                $result = $count == 0 ? 'No inspectors names updated' : $count.' inspector names updated!' ;
                $output->writeln($result);
                break;

            case 2:
                $count = $this->addMissingInspectors();
                $result = $count == 0 ? 'No new inspectors added' : $count.' new inspectors added!' ;
                $output->writeln($result);
                break;

            case 3:
                $this->fixDuplicateInspectors();
                $output->writeln('DONE');
                break;

            case 4:
                $this->authorizeInspectorsForExteriorMeasurementsTexelaar();
                $output->writeln('DONE');
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }

    }


    private function fixInspectorNames()
    {
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

            $convertedNameArray = InspectorMigrator::convertImportedInspectorName($lastName);
            $newFirstName = $convertedNameArray[JsonInputConstant::FIRST_NAME];
            $newLastName = $convertedNameArray[JsonInputConstant::LAST_NAME];

            if($newLastName != null && $newFirstName != null) {
                $sql = "UPDATE person SET first_name = '".$newFirstName."', last_name = '".$newLastName."'
                        WHERE id = ".$id;
                $this->conn->exec($sql);
                $updateCount++;
            }
        }

        return $updateCount;
    }


    private function addMissingInspectors()
    {
        $newInspectorCount = 0;

        $newInspectorCount += $this->addMissingInspector('Johan', 'Knaap');
        $newInspectorCount += $this->addMissingInspector('Ido', 'Altenburg');
        $newInspectorCount += $this->addMissingInspector('', 'Niet NSFO');

        return $newInspectorCount;
    }


    /**
     * Return number of new inspectors added.
     *
     * @param string $firstName
     * @param string $lastName
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function addMissingInspector($firstName, $lastName)
    {
        $sql = "SELECT COUNT(*) FROM inspector i
                  INNER JOIN person p ON i.id = p.id
                WHERE first_name = '".$firstName."' AND last_name = '".$lastName."'";
        $count = $this->conn->query($sql)->fetch()['count'];

        if($count == 0) {
            $this->inspectorRepository->insertNewInspector($firstName, $lastName);
            return 1;
        }
        return 0;
    }
    
    
    private function fixDuplicateInspectors()
    {
        $sql = "SELECT x.id, x.first_name, x.last_name FROM person x
                INNER JOIN (
                    SELECT p.first_name, p.last_name, p.type FROM inspector i
                      INNER JOIN person p ON i.id = p.id
                      WHERE p.type = 'Inspector'
                    GROUP BY p.first_name, p.last_name, p.type HAVING COUNT(*) > 1
                    )y ON y.first_name = x.first_name AND y.last_name = x.last_name AND y.type = x.type";
        $results = $this->conn->query($sql)->fetchAll();

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
            $this->output->writeln('No duplicate inspectors!');
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


    private function authorizeInspectorsForExteriorMeasurementsTexelaar()
    {
        $this->actionByAdmin = $this->adminRepository->find(self::ACTION_BY_ADMIN_ID);

        $inspectors = [
            'Hans' => 'te Mebel',
            'Marjo' => 'van Bergen',
            'Wout' => 'Rodenburg',
            'Johan' => 'Knaap',
            'Ido' => 'Altenburg',
            '' => 'Niet NSFO',
        ];

        $this->cmdUtil->setStartTimeAndPrintIt(count($inspectors) * 2, 1, 'Authorize inspectors for Texelaars');

        $authorizations = 0;
        $inspectorCount = 0;
        foreach ($inspectors as $firstName => $lastName) {
            $authorizations += $this->authorizeInspectorForExteriorMeasurementsTexelaar($firstName, $lastName);
            $inspectorCount++;
            $this->cmdUtil->advanceProgressBar(1, 'NewAuthorizations|InspectorsChecked: '.$authorizations.'|'.$inspectorCount);
        }
        $this->em->flush();
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param $firstName
     * @param $lastName
     * @return int
     */
    private function authorizeInspectorForExteriorMeasurementsTexelaar($firstName, $lastName)
    {
        $pedigreeRegisterTexelaarNTS = $this->pedigreeRegisterRepository->findOneBy(['abbreviation' => 'NTS']);
        $pedigreeRegisterTexelaarTSNH = $this->pedigreeRegisterRepository->findOneBy(['abbreviation' => 'TSNH']);

        $count = 0;
        /** @var Inspector $inspector */
        $inspector = $this->inspectorRepository->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if($inspector != null) {
            $count += $this->authorizeInspector($inspector, InspectorMeasurementType::EXTERIOR, $pedigreeRegisterTexelaarNTS);
            $count += $this->authorizeInspector($inspector, InspectorMeasurementType::EXTERIOR, $pedigreeRegisterTexelaarTSNH);
            return $count;
        }
        return $count;
    }


    /**
     * @param Inspector $inspector
     * @param string $measurementType
     * @param PedigreeRegister $pedigreeRegister
     * @return int
     */
    private function authorizeInspector(Inspector $inspector, $measurementType, PedigreeRegister $pedigreeRegister = null)
    {
        $inspectorAuthorization = $this->inspectorAuthorizationRepository->findOneBy(
            ['inspector' => $inspector, 'measurementType' => $measurementType, 'pedigreeRegister' => $pedigreeRegister]);

        if($inspectorAuthorization == null) {
            $inspectorAuthorization = new InspectorAuthorization(
                $inspector, $this->actionByAdmin, $measurementType, $pedigreeRegister);
            $this->em->persist($inspectorAuthorization);
            return 1;
        }
        return 0;
    }
}
