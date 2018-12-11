<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NsfoWorkerFeedbackCommand extends ContainerAwareCommand
{
    const TITLE = 'Process SQS messages as worker task, usually sent by Java internal worker';

    protected function configure()
    {
        $this
            ->setName('nsfo:worker:feedback')
            ->setDescription(self::TITLE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sqsFeedbackProcessor = $this->getContainer()->get('AppBundle\Processor\SqsFeedbackProcessor');
        $sqsFeedbackProcessor->process();
    }

}
