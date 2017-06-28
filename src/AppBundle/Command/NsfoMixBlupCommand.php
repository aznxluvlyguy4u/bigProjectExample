<?php

namespace AppBundle\Command;

use AppBundle\Entity\BreedValue;
use AppBundle\Service\BreedIndexService;
use AppBundle\Service\BreedValueService;
use AppBundle\Service\MixBlupInputFilesService;
use AppBundle\Service\MixBlupOutputFilesService;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class NsfoMixBlupCommand
 * @package AppBundle\Command
 */
class NsfoMixBlupCommand extends ContainerAwareCommand
{
    const TITLE = 'MixBlup';
    const DEFAULT_OPTION = 0;

    const CREATE_TEST_FOLDER_IF_NULL = true;

    /** @var ObjectManager $em */
    private $em;

    /** @var Connection $conn */
    private $conn;

    /** @var CommandUtil */
    private $cmdUtil;

    /** @var MixBlupInputFilesService */
    private $mixBlupInputFilesService;
    /** @var MixBlupOutputFilesService */
    private $mixBlupOutputFilesService;
    /** @var BreedIndexService */
    private $breedIndexService;
    /** @var BreedValueService */
    private $breedValueService;

    protected function configure()
    {
        $this
            ->setName('nsfo:mixblup')
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
        $this->mixBlupInputFilesService = $this->getContainer()->get('app.mixblup.input');
        $this->mixBlupOutputFilesService = $this->getContainer()->get('app.mixblup.output');
        $this->breedIndexService = $this->getContainer()->get('app.breed.index');
        $this->breedValueService = $this->getContainer()->get('app.breed.value');

        //Print intro
        $output->writeln(CommandUtil::generateTitle(self::TITLE));
        $output->writeln([DoctrineUtil::getDatabaseHostAndNameString($em),'']);

        $option = $this->cmdUtil->generateMultiLineQuestion([
            'Choose option: ', "\n",
            '1: Generate & Upload MixBlupInputFiles and send message to MixBlup queue', "\n",
            '2: Download and process MixBlup output files (relani & solani)', "\n",
            '3: Generate MixBlup instruction files only', "\n",
            '4: Initialize blank genetic bases', "\n",
            '========================================================================', "\n",
            '10: Initialize BreedIndexType and BreedValueType', "\n",
            '11: Delete all duplicate breedValues', "\n",
            'DEFAULT: Abort', "\n"
        ], self::DEFAULT_OPTION);

        switch ($option) {

            case 1:
                $this->mixBlupInputFilesService->run();
                break;
            case 2:
                $this->mixBlupOutputFilesService->run();
                break;
            case 3:
                $this->mixBlupInputFilesService->writeInstructionFiles();
                break;

            case 4:
                $this->breedValueService->initializeBlankGeneticBases();
                break;

            case 10:
                $this->breedIndexService->initializeBreedIndexType();
                $this->breedValueService->initializeBreedValueType();
                break;
            case 11:
                $deleteCount = MixBlupOutputFilesService::deleteDuplicateBreedValues($this->conn);
                $message = $deleteCount > 0 ? $deleteCount . ' duplicate breedValues were deleted' : 'No duplicate breedValues found';
                $this->cmdUtil->writeln($message);
                break;

            default:
                $output->writeln('ABORTED');
                break;
        }
        $output->writeln('DONE');


    }


}
