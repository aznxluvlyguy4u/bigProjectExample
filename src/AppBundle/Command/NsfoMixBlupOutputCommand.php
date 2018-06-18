<?php

namespace AppBundle\Command;

use AppBundle\Service\MixBlupOutputFilesService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoMixBlupOutputCommand extends ContainerAwareCommand
{
    const TITLE = 'Download and process MiXBLUP output files';

    /** @var MixBlupOutputFilesService */
    private $mixBlupOutputFilesService;

    protected function configure()
    {
        $this
            ->setName('nsfo:mixblup:output')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mixBlupOutputFilesService = $this->getContainer()->get('AppBundle\Service\MixBlupOutputFilesService');
        $this->mixBlupOutputFilesService->run();
    }

}
