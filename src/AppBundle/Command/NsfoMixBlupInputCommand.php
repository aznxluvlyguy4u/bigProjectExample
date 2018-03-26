<?php

namespace AppBundle\Command;

use AppBundle\Service\MixBlupInputFilesService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMixBlupInputCommand extends ContainerAwareCommand
{
    const TITLE = 'Generate and upload MiXBLUP input files';

    /** @var MixBlupInputFilesService */
    private $mixBlupInputFilesService;

    protected function configure()
    {
        $this
            ->setName('nsfo:mixblup:input')
            ->setDescription(self::TITLE)
            ->addOption('option', 'o', InputOption::VALUE_REQUIRED,
                'Only run for given analysisType: 1 = LambMeatIndex, 2 = Fertility, 3 = Exterior, 4 = WormResistance')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mixBlupInputFilesService = $this->getContainer()->get('app.mixblup.input');

        $option = intval(ltrim($input->getOption('option'),'='));

        switch ($option) {

            case 1:
                $output->writeln('Generating only LambMeatIndex input files ...');
                $this->mixBlupInputFilesService->runLambMeatIndex();
                break;

            case 2:
                $output->writeln('Generating only Fertility input files ...');
                $this->mixBlupInputFilesService->runFertility();
                break;

            case 3:
                $output->writeln('Generating only Exterior input files ...');
                $this->mixBlupInputFilesService->runExterior();
                break;

            case 4:
                $output->writeln('Generating only WormResistance input files ...');
                $this->mixBlupInputFilesService->runWorm();
                break;

            default:
                $output->writeln('Generating input files for all AnalysisTypes ...');
                $this->mixBlupInputFilesService->run();
                break;
        }
    }

}
