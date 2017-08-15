<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Util\CommandUtil;
use AppBundle\Util\CsvParser;

/**
 * Class MixBlupInputFileValidator
 */
class MixBlupInputFileValidator
{
    /** @var CommandUtil */
    private $cmdUtil;

    /** @var string */
    private $cacheDir;
    /** @var array */
    private $data;


    /**
     * MixBlupInputFileValidator constructor.
     * @param $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public function validateUbnOfBirthInPedigreeFile(CommandUtil $cmdUtil)
    {
        return $this->validateUbnOfBirth(
            $this->cacheDir.'/mixblup/pedigree/',
            'PedVruchtb.txt',
            3,
            $cmdUtil);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public function validateUbnOfBirthInDataFile(CommandUtil $cmdUtil)
    {
        return $this->validateUbnOfBirth(
            $this->cacheDir.'/mixblup/data/',
            'DataVruchtb.txt',
            28,
            $cmdUtil);
    }


    /**
     * @param $folder
     * @param $filename
     * @param $ubnColumnIndex
     * @param CommandUtil $cmdUtil
     * @return int
     */
    private function validateUbnOfBirth($folder, $filename, $ubnColumnIndex, CommandUtil $cmdUtil)
    {
        if ($this->cmdUtil === null) {
            $this->cmdUtil = $cmdUtil;
        }

        $this->cmdUtil->writeln('Validating ubnOfBirth in '.$filename. ' in column with index '.$ubnColumnIndex);
        $this->cmdUtil->writeln('Parsing '.$folder.$filename. ' ...');

        $this->data = CsvParser::parseSpaceSeparatedFile($folder, $filename);

        $this->cmdUtil->setStartTimeAndPrintIt(count($this->data), 1);

        $nullReplacement = MixBlupInstructionFileBase::MISSING_BLOCK_REPLACEMENT;

        $errors = [];
        $errorCount = 0;
        $this->cmdUtil->getProgressBar()->clear();
        foreach ($this->data as $key => $result)
        {
            $animalId = $result[0];
            $ubn = $result[$ubnColumnIndex];

            if (!self::isValidUbnOfBirth($ubn)) {
                $error = ['row' => $key, 'animalId' => $animalId, 'ubn' => $ubn];
                $errorCount++;
                $errors[] = $error;
            }

            $this->cmdUtil->advanceProgressBar(1, 'Errors: '.$errorCount);
        }
        $this->cmdUtil->getProgressBar()->display();
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        if ($errorCount === 0) {
            $this->cmdUtil->writeln('There were no errors found');
        } else {
            $this->cmdUtil->writeln($errors);
            $this->cmdUtil->writeln($errorCount . ' errors where found');
        }
        return $errorCount;
    }


    /**
     * The max integer value in MiXBLUP
     *
     * @return number
     */
    public static function maxIntVal()
    {
        return pow(2,31) - 1;
    }


    /**
     * @param $ubnOfBirth
     * @return int
     */
    public static function isValidUbnOfBirth($ubnOfBirth)
    {
        if (filter_var($ubnOfBirth, FILTER_VALIDATE_INT)) {
            $ubnOfBirth = intval($ubnOfBirth);
            return $ubnOfBirth <= self::maxIntVal() //max limit
                && $ubnOfBirth === abs($ubnOfBirth); //must be positive
        }
        return false;
    }


    /**
     * @param $ubnOfBirth
     * @param int $nullReplacement
     * @return int
     */
    public static function getValidatedUbnOfBirth($ubnOfBirth, $nullReplacement = MixBlupInstructionFileBase::MISSING_BLOCK_REPLACEMENT)
    {
        return self::isValidUbnOfBirth($ubnOfBirth) ? intval($ubnOfBirth) : $nullReplacement;
    }
}