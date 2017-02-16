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
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class NsfoInspectorCommand extends ContainerAwareCommand
{
    const TITLE = 'Inspectors';
    const DEFAULT_OPTION = 0;
    const ACTION_BY_ADMIN_ID = 2152; //Reinard Everts

    CONST NAME_CORRECTIONS = 'finder_name_corrections';
    CONST NEW_NAMES = 'finder_name_new';

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name_corrections' => 'inspector_name_corrections.csv',
        'finder_name_new' => 'inspector_new_names.csv',
        'ignoreFirstLine' => true
    );


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

        $this->cmdUtil->printTitle(self::TITLE);
        $this->output->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

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
        $csv = $this->parseCSV(self::NAME_CORRECTIONS);
        return InspectorMigrator::fixInspectorNames($this->conn, $csv);
    }


    private function addMissingInspectors()
    {
        $csv = $this->parseCSV(self::NEW_NAMES);
        return InspectorMigrator::addMissingInspectors($this->conn, $this->inspectorRepository, $csv);
    }
    
    
    private function fixDuplicateInspectors()
    {
        InspectorMigrator::fixDuplicateInspectors($this->conn, $this->cmdUtil, $this->inspectorRepository);
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


    /**
     * @param string $fileKey
     * @return array
     */
    private function parseCSV($fileKey) {
        $ignoreFirstLine = $this->csvParsingOptions['ignoreFirstLine'];

        $finder = new Finder();
        $finder->files()
            ->in($this->csvParsingOptions['finder_in'])
            ->name($this->csvParsingOptions[$fileKey])
        ;
        foreach ($finder as $file) { $csv = $file; }

        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
                $i++;
                if ($ignoreFirstLine && $i == 1) { continue; }
                $rows[] = $data;
                gc_collect_cycles();
            }
            fclose($handle);
        }

        return $rows;
    }
}
