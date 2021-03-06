<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Entity\Employee;
use AppBundle\Entity\EmployeeRepository;
use AppBundle\Enumerator\GenderType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
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
    const DEFAULT_START_UNIT = 1;

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
        return $this->generateMultiLineQuestion([  ' ',
            $question,
            ':   '], $defaultAnswer, $isCleanupString);
    }


    /**
     * @param array $questionArray
     * @param string $defaultAnswer
     * @param bool $isCleanupString
     * @return string
     */
    public function generateMultiLineQuestion($questionArray, $defaultAnswer, $isCleanupString = true)
    {
        $question = new Question($questionArray, $defaultAnswer);
        $answer = $this->helper->ask($this->inputInterface, $this->outputInterface, $question);

        if($isCleanupString) {
            $answer = str_replace(array(' ', "\n", "\t", "\r", "'"), '', $answer);
        }

        return $answer;
    }


    /**
     * @param string $confirmationQuestion
     * @param bool $includeDefaultOptionText
     * @param bool $logAnswer
     * @return bool
     */
    public function generateConfirmationQuestion($confirmationQuestion = 'Continue with this action?',
                                                 $includeDefaultOptionText = false,
                                                 $logAnswer = false)
    {
        $defaultAnswer = false;
        $fullConfirmationQuestion = $confirmationQuestion;
        if ($includeDefaultOptionText) {
            $fullConfirmationQuestion = $confirmationQuestion
            .'  (y/n, default is '.StringUtil::getBooleanAsString($defaultAnswer).')';
        }

        $question = new ConfirmationQuestion(
            $fullConfirmationQuestion,
            $defaultAnswer,
            '/^(y|j)/i' //Anything starting with y or j is accepted
        );

        $answer = !$defaultAnswer;
        if (!$this->helper->ask($this->inputInterface, $this->outputInterface, $question)) {
            $answer = $defaultAnswer;
        }

        if ($logAnswer) {
            $this->writeln(rtrim($confirmationQuestion,'?').': '. StringUtil::getBooleanAsString($answer));
        }

        return $answer;
    }


    /**
     * @param int $totalNumberOfUnits
     * @param int $startUnit
     * @param string $startMessage
     */
    public function setStartTimeAndPrintIt($totalNumberOfUnits = null, $startUnit = null, $startMessage = self::DEFAULT_PROGRESS_BAR_START_MESSAGE)
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
            $this->progressBar->advance($unitsToAdvance);
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
     * @param $heading
     */
    public function printTitle($heading)
    {
        $this->outputInterface->writeln(self::generateTitle($heading));
    }


    /**
     * @param $input
     */
    public function write($input)
    {
        if(!is_array($input)) {
            $this->outputInterface->write($input);
        } else {
            $this->writeln($input);
        }
    }


    /**
     * @return OutputInterface
     */
    public function getOutputInterface()
    {
        return $this->outputInterface;
    }

    /**
     * @return InputInterface
     */
    public function getInputInterface() {
        return $this->inputInterface;
    }


    /**
     * @param $line
     */
    public function writelnWithTimestamp($line)
    {
        $line = is_string($line) ? TimeUtil::getTimeStampNow() . ': ' .$line : $line;
        $this->writeln($line);
    }


    /**
     * @param $input
     */
    public function writelnClean($input)
    {
        $this->writeln($input, 0, false);
    }


    /**
     * @param $input
     * @param int $indentLevel
     * @param boolean $printKeys
     */
    public function writeln($input, $indentLevel = 0, $printKeys = true)
    {
        if(!is_array($input)) {
            $this->outputInterface->writeln($input);

        } else {
            foreach ($input as $key => $value) {
                $this->indent($indentLevel);

                if(is_array($value)) {
                    if ($printKeys) {
                        $this->writeln($key.' : {', $indentLevel, $printKeys);
                        $this->indent($indentLevel);
                        $this->writeln($value, $indentLevel+1, $printKeys);
                        $this->indent($indentLevel);
                        $this->writeln('   }', $indentLevel, $printKeys);
                    } else {
                        $this->writeln('{', $indentLevel, $printKeys);
                        $this->indent($indentLevel);
                        $this->writeln($value, $indentLevel+1, $printKeys);
                        $this->indent($indentLevel);
                        $this->writeln('}', $indentLevel, $printKeys);
                    }
                } else {
                    if ($printKeys) {
                        $this->writeln($key.' : '.$value, $indentLevel, $printKeys);
                    } else {
                        $this->writeln($value, $indentLevel, $printKeys);
                    }
                }
            }
        }
    }


    /**
     * @param int $indentCount
     * @param string $indentType
     */
    private function indent($indentCount = 1, $indentType = '      ')
    {
        $this->outputInterface->write(str_repeat($indentType, $indentCount));
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


    public function printClosingLine()
    {
        $this->writeLn('--------------------------------------------');
        $this->writeLn(' ');
        $this->writeLn(' ');
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
                $uln = $parent->getUln();
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


    /**
     * @param ObjectManager $em
     * @param string $minimumAccessLevel
     * @param boolean $includeDevelopers
     * @return Employee
     */
    public function questionForAdminChoice(ObjectManager $em, $minimumAccessLevel, $includeDevelopers)
    {
        /** @var EmployeeRepository $employeeRepository */
        $employeeRepository = $em->getRepository(Employee::class);
        $employees = $employeeRepository->findByMinimumAccessLevel($minimumAccessLevel, $includeDevelopers);

        $clude = $includeDevelopers ? 'including' : 'excluding';
        if(count($employees) == 0) {
            $this->writeln('There are no '.$minimumAccessLevel.' of higher in the database (
            '.$clude.' DEVELOPERS). Create one first');
        }

        $employeeSelectionArray = [];
        $i = 1;

        $this->writeln($minimumAccessLevel.'s or higher in the database '.$clude.' DEVELOPERS');
        /** @var Employee $employee */
        foreach ($employees as $employee) {
            $this->writeln($i.' : '.$employee->getFullName());
            $employeeSelectionArray[$i] = $employee;
            $i++;
        }

        $lastCount = count($employeeSelectionArray);
        $defaultAdmin = $employeeSelectionArray[$lastCount];

        do{
            do {
                $choice = $this->generateQuestion('Choose your Admin. Insert their number: (default = '
                    .$defaultAdmin->getFullName().')', $lastCount);
            } while (!key_exists($choice, $employeeSelectionArray));

            $employee = $employeeSelectionArray[$choice];
            $this->writeln('You chose: '.$employee->getFullName());

            $continue = !$this->generateConfirmationQuestion('Is this correct? (y/n, default = no)');
        } while ($continue);

        return $employee;
    }


    /**
     * @param int $defaultIntValue
     * @param string $intLabel
     * @return int
     */
    public function questionForIntChoice($defaultIntValue = 0, $intLabel)
    {
        do{
            do {
                $choice = $this->generateQuestion('Insert integer for '.$intLabel. ' value (default = '
                    .$defaultIntValue.')', $defaultIntValue);
            } while (!ctype_digit($choice) && !is_int($choice));

            $intChoice = intval($choice);
            $this->writeln('You chose: '.$intChoice);

            $continue = !$this->generateConfirmationQuestion('Is this correct? (y/n, default = no)');
        } while ($continue);

        return $intChoice;
    }
}