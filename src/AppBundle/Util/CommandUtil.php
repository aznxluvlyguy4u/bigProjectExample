<?php

namespace AppBundle\Util;


use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\GenderType;
use AppBundle\Report\Mixblup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Metadata\Tests\Driver\Fixture\C\SubDir\C;
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
    
    
}