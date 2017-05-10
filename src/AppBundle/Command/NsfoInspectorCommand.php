<?php

namespace AppBundle\Command;

use AppBundle\Entity\Inspector;
use AppBundle\Entity\InspectorRepository;
use AppBundle\Enumerator\AccessLevelType;
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

    CONST NAME_CORRECTIONS = 'finder_name_corrections';
    CONST NEW_NAMES = 'finder_name_new';
    CONST AUTHORIZE_TEXELAAR = 'finder_authorize_texelaar';

    private $csvParsingOptions = array(
        'finder_in' => 'app/Resources/imports/',
        'finder_out' => 'app/Resources/outputs/',
        'finder_name_corrections' => 'inspector_name_corrections.csv',
        'finder_name_new' => 'inspector_new_names.csv',
        'finder_authorize_texelaar' => 'authorize_inspectors_texelaar.csv',
        'ignoreFirstLine' => true
    );


    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var InspectorRepository */
    private $inspectorRepository;

    /** @var CommandUtil */
    private $cmdUtil;
    
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
        $helper = $this->getHelper('question');
        $this->cmdUtil = new CommandUtil($input, $output, $helper);

        $this->inspectorRepository = $em->getRepository(Inspector::class);

        $this->cmdUtil->printTitle(self::TITLE);
        $this->cmdUtil->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Fix inspector names', "\n",
            '2: Add missing inspectors', "\n",
            '3: Fix duplicate inspectors', "\n",
            '4: Authorize inspectors', "\n",
            '5: Generate inspectorCodes, if null', "\n",
            'abort (other)', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                $csv = $this->parseCSV(self::NAME_CORRECTIONS);
                $count = InspectorMigrator::fixInspectorNames($this->conn, $csv);
                $result = $count == 0 ? 'No inspectors names updated' : $count.' inspector names updated!' ;
                $output->writeln($result);
                break;

            case 2:
                $csv = $this->parseCSV(self::NEW_NAMES);
                $count = InspectorMigrator::addMissingInspectors($this->conn, $this->inspectorRepository, $csv);
                $result = $count == 0 ? 'No new inspectors added' : $count.' new inspectors added!' ;
                $output->writeln($result);
                break;

            case 3:
                InspectorMigrator::fixDuplicateInspectors($this->conn, $this->cmdUtil, $this->inspectorRepository);
                $output->writeln('DONE');
                break;

            case 4:
                $csv = $this->parseCSV(self::AUTHORIZE_TEXELAAR);
                $admin = $this->cmdUtil->questionForAdminChoice($this->em, AccessLevelType::SUPER_ADMIN, false);
                InspectorMigrator::authorizeInspectorsForExteriorMeasurementsTexelaar($this->em, $this->cmdUtil, $csv, $admin);
                $output->writeln('DONE');
                break;
            
            case 5:
                $updateCount = InspectorMigrator::generateInspectorCodes($this->conn);
                $result = $updateCount == 0 ? 'No new inspectorCodes added' : $updateCount.' new inspectorCodes added!' ;
                $output->writeln($result);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }

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
