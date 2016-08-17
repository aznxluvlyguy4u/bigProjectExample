<?php

namespace AppBundle\Util;


use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CommandUtil
{
    /** @var InputInterface */
    private $inputInterface;

    /** @var OutputInterface */
    private $outputInterface;

    /** @var QuestionHelper */
    private $helper;

    /** @var \DateTime */
    private $startTime;

    /** @var \DateTime */
    private $endTime;

    /**
     * CommandUtil constructor.
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $helper
     */
    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $helper)
    {
        $this->inputInterface = $input;
        $this->outputInterface = $output;
        $this->helper = $helper;
    }

    /**
     * @param string $question
     * @param string $defaultAnswer
     * @param bool $isCleanupString
     * @return string
     */
    public function generateQuestion($question, $defaultAnswer, $isCleanupString = true)
    {
        $question = new Question([  ' ',
                                    $question,
                ':   ']
            , $defaultAnswer);
        $answer = $this->helper->ask($this->inputInterface, $this->outputInterface, $question);

        if($isCleanupString) {
            $answer = str_replace(array(' ', "\n", "\t", "\r", "'"), '', $answer);
        }

        return $answer;
    }

    /**
     * @return bool
     */
    public function generateConfirmationQuestion($confirmationQuestion = 'Continue with this action?')
    {
        $question = new ConfirmationQuestion(
            $confirmationQuestion,
            false,
            '/^(y|j)/i' //Anything starting with y or j is accepted
        );

        if (!$this->helper->ask($this->inputInterface, $this->outputInterface, $question)) {
            return false;
        } else {
            return true;
        }
    }

    
    public function setStartTimeAndPrintIt()
    {
        $this->startTime = new \DateTime();
        $this->outputInterface->writeln(['Start time: '.date_format($this->startTime, 'Y-m-d h:i:s'),'']);
    }


    public function setEndTimeAndPrintFinalOverview()
    {
        $this->endTime = new \DateTime();
        $elapsedTime = gmdate("H:i:s", $this->endTime->getTimestamp() - $this->startTime->getTimestamp());

        $this->outputInterface->writeln([
            '=== PROCESS FINISHED ===',
            'End Time: '.date_format($this->endTime, 'Y-m-d h:i:s'),
            'Elapsed Time (h:i:s): '.$elapsedTime,
            '',
            '']);
    }
    

    /**
     * @param string $heading
     * @return array
     */
    public static function generateTitle($heading)
    {
        return [' ',
            $heading,
            $line = str_repeat('=', strlen($heading)),
            '',
        ];
    }
}