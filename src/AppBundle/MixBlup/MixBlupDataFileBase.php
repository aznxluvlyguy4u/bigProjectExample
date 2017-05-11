<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\NullChecker;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

/**
 * Class MixBlupDataFileBase
 * @package AppBundle\MixBlup
 */
class MixBlupDataFileBase
{
    /** @var Connection */
    protected $conn;

    /** @var ObjectManager */
    protected $em;

    /** @var string */
    protected $outputFolderPath;

    /**
     * MixBlupDataFileBase constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->outputFolderPath = $outputFolderPath;
        NullChecker::createFolderPathIfNull($outputFolderPath);
    }


    /**
     * @return array
     */
    protected function getInstructionFileDefaultEnding()
    {
        return [
            'PEDFILE   '.MixBlupSetting::PEDIGREE_FILENAME.'.txt',
            ' animal    A !missing '.MixBlupNullFiller::ULN.' #uln',
            ' sire      A !missing '.MixBlupNullFiller::ULN.' #uln van vader',
            ' dam       A !missing '.MixBlupNullFiller::ULN.' #uln van moeder',
            ' block     I !BLOCK', //NOTE it is an integer here
            ' gender    A !missing '.MixBlupNullFiller::GENDER,
            ' gebjaar   A !missing '.MixBlupNullFiller::DATE.' #geboortedatum',
            ' rascode   A !missing '.MixBlupNullFiller::CODE,

            'PARFILE  '.MixBlupSetting::PARFILE_FILENAME,

            'MODEL', //TODO check the MODEL & SOLVING settings
            ' bw1    ~  herd sex !random comenv G(animal)',
            ' bw2    ~  herd sex !random comenv G(animal)',

            'SOLVING',
            'TMPDiR .',
            'END',
        ];
    }


    /**
     * @return array
     */
    protected function getStandardizedBreedCodePartsAndHetRecOfInstructionFile()
    {
        return [
            ' CovTE      I !missing '.MixBlupNullFiller::COUNT,  //TE, BT, DK are genetically all the same
            ' CovCF      I !missing '.MixBlupNullFiller::COUNT,  //Clun Forest
            ' CovBM      I !missing '.MixBlupNullFiller::COUNT,  //Bleu du Maine
            ' CovSW      I !missing '.MixBlupNullFiller::COUNT,  //Swifter
            ' CovNH      I !missing '.MixBlupNullFiller::COUNT,  //Noordhollander
            ' CovFL      I !missing '.MixBlupNullFiller::COUNT,  //Flevolander
            ' CovHD      I !missing '.MixBlupNullFiller::COUNT,  //Hampshire Down
            ' CovOV      I !missing '.MixBlupNullFiller::COUNT,  //other  (NN means unknown, also include it here)
            ' CovHet     T !missing '.MixBlupNullFiller::HETEROSIS.' #Heterosis van het dier',
            ' CovRec     T !missing '.MixBlupNullFiller::RECOMBINATION.' #Recombinatie van het dier',
        ];
    }


    /**
     * @param array $records
     * @param string $filename
     * @return bool
     */
    protected function writeToFile(array $records, $filename)
    {
        if(!is_string($filename) || $filename == '') { return false; }

        $filePath = $this->outputFolderPath.'/'.$filename;

        //purge current file content
        file_put_contents($filePath, "");

        foreach ($records as $record) {
            file_put_contents($filePath, $record."\n", FILE_APPEND);
        }

        return true;
    }


}