<?php

namespace AppBundle\Command;

use AppBundle\Enumerator\ProcessCommandOptions;
use AppBundle\Exception\Cli\InvalidInputCliException;
use AppBundle\Service\Task\ResidenceFixTaskService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoResidencesFixCommand extends ContainerAwareCommand
{
    const TITLE = 'FIX ALL ANIMAL RESIDENCES BY CURRENT LOCATION OF ANIMALS';

    protected function configure()
    {
        $this
            ->setName('nsfo:residences:fix')
            ->setDescription(self::TITLE)
            ->addOption('option', 'o', InputOption::VALUE_REQUIRED,
                "Options:".ProcessCommandOptions::optionsAsList())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $option = ProcessCommandOptions::getSelectedOptions($input);

        /** @var ResidenceFixTaskService $taskService */
        $taskService = $this->getContainer()->get(ResidenceFixTaskService::class);

        switch ($option) {
            case ProcessCommandOptions::START: $taskService->start(); break;
            case ProcessCommandOptions::CANCEL: $taskService->cancel(); break;
            case ProcessCommandOptions::RUN: $taskService->run(); break;
            default: throw new InvalidInputCliException(ProcessCommandOptions::invalidInputText($option));
        }
    }
}
