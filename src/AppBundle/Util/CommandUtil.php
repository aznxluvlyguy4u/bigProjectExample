<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\GenderType;
use AppBundle\Report\Mixblup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class CommandUtil
{
    //ProgressBar settings
    const DEFAULT_PROGRESS_BAR_START_MESSAGE = '*systems online*';
    const DEFAULT_PROGRESS_BAR_RUNNING_MESSAGE = '*processing*';
    const DEFAULT_PROGRESS_BAR_END_MESSAGE = '*completed*';
    const DEFAULT_TOTAL_UNITS = 100;
    const DEFAULT_START_UNIT = 0;

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

    /** @var \DateTime */
    private $elapsedTimeStart;

    /** @var ProgressBar */
    private $progressBar;

    /** @var Boolean */
    private $isProgressBarActive;
    

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
        $this->isProgressBarActive = false;
    }


    /**
     * @return ProgressBar
     */
    public function getProgressBar() { return $this->progressBar; }


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


    /**
     * @param int $totalNumberOfUnits
     * @param int $startUnit
     * @param string $startMessage
     */
    public function setStartTimeAndPrintIt($totalNumberOfUnits = self::DEFAULT_TOTAL_UNITS, $startUnit = self::DEFAULT_START_UNIT, $startMessage = self::DEFAULT_PROGRESS_BAR_START_MESSAGE)
    {
        $this->startTime = new \DateTime();
        $this->elapsedTimeStart = $this->startTime;
        $this->outputInterface->writeln(['Start time: '.date_format($this->startTime, 'Y-m-d H:i:s'),'']);

        if($totalNumberOfUnits !== null && $startUnit !== null) {
            $this->isProgressBarActive = true;

            if($totalNumberOfUnits < 1) { $totalNumberOfUnits = self::DEFAULT_TOTAL_UNITS; }
            if($startUnit < 1) { $startUnit = self::DEFAULT_START_UNIT; }
            if($startMessage == null) { $startMessage = self::DEFAULT_PROGRESS_BAR_START_MESSAGE; }

            $this->progressBar = new ProgressBar($this->outputInterface, $totalNumberOfUnits);
            $this->progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%  -  %message%');

            $this->progressBar->setMessage($startMessage);
            $this->progressBar->start();
            $this->progressBar->setProgress($startUnit);
        }
    }

    /**
     * @param string $message
     */
    public function setProgressBarMessage($message)
    {
        $this->progressBar->setMessage($message);
    }

    /**
     * @param int $unitsToAdvance
     * @param string $message
     */
    public function advanceProgressBar($unitsToAdvance = 1, $message = self::DEFAULT_PROGRESS_BAR_RUNNING_MESSAGE)
    {
        if($unitsToAdvance != null && $unitsToAdvance > 0) {
            $this->progressBar->advance(1);
            if($message != null) {
                $this->progressBar->setMessage($message);
            } else {
                $this->progressBar->setMessage(self::DEFAULT_PROGRESS_BAR_RUNNING_MESSAGE);
            }
        }
    }


    public function printElapsedTime($label = 'Elapsed time from start or previous elapsed time')
    {
        $now = new \DateTime();
        $elapsedTime = gmdate("H:i:s", $now->getTimestamp() - $this->elapsedTimeStart->getTimestamp());
        $this->outputInterface->writeln(['',$label.': '.$elapsedTime,'']);
        $this->elapsedTimeStart = $now;
    }


    public function setEndTimeAndPrintFinalOverview()
    {
        $this->endTime = new \DateTime();
        $elapsedTime = gmdate("H:i:s", $this->endTime->getTimestamp() - $this->startTime->getTimestamp());

        $this->outputInterface->writeln([
            ' ',
            ' ',
            '=== PROCESS FINISHED ===',
            '']);

        if($this->isProgressBarActive) {
            if($this->progressBar->getMessage() == self::DEFAULT_PROGRESS_BAR_RUNNING_MESSAGE) {
                $this->progressBar->setMessage(self::DEFAULT_PROGRESS_BAR_END_MESSAGE);
            }
            $this->progressBar->finish();
            $this->outputInterface->writeln([' ',' ']);
        }

        $this->outputInterface->writeln([
            'End Time: '.date_format($this->endTime, 'Y-m-d H:i:s'),
            'Elapsed Time (H:i:s): '.$elapsedTime,
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

    
    /**
     * @param Collection $parents
     * @return ArrayCollection
     */
    public static function getParentsFromParentsArray($parents, $nullFiller = null)
    {
        $result  = new ArrayCollection();
        $result->set(Constant::FATHER_NAMESPACE, $nullFiller);
        $result->set(Constant::MOTHER_NAMESPACE, $nullFiller);

        foreach($parents as $parent) {
            /** @var Animal $parent */
            $gender = $parent->getGender();
            if($gender == GenderType::M || $gender == GenderType::MALE) {
                $result->set(Constant::FATHER_NAMESPACE, $parent);
            } else if($gender == GenderType::V || $gender == GenderType::FEMALE) {
                $result->set(Constant::MOTHER_NAMESPACE, $parent);
            }
        }

        return $result;
    }
    

    /**
     * @param Collection $parents
     * @return ArrayCollection
     */
    public static function getParentUlnsFromParentsArray($parents, $nullFiller = null)
    {
        $parents = self::getParentsFromParentsArray($parents, $nullFiller);

        $parentLabels = array();
        $parentLabels[] = Constant::FATHER_NAMESPACE;
        $parentLabels[] = Constant::MOTHER_NAMESPACE;

        foreach($parentLabels as $parentLabel) {

            /** @var Animal $parent */
            $parent = $parents->get($parentLabel);

            if($parent instanceof Animal) {
                $uln = Mixblup::formatUln($parent, $nullFiller);
                $parents->set($parentLabel, $uln);
            }
        }

        return $parents;
    }


    /**
     * @param $inputFolderPath
     * @return array
     */
    public static function getRowsFromCsvFileWithoutHeader($inputFolderPath)
    {
        $fileContents = file_get_contents($inputFolderPath);
        $data = explode(PHP_EOL, $fileContents);
        array_shift($data); //remove first row

        return $data;
    }
}