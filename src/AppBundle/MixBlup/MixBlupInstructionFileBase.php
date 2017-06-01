<?php


namespace AppBundle\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;

/**
 * Class MixBlupInstructionFileBase
 * @package AppBundle\MixBlup
 */
abstract class MixBlupInstructionFileBase
{
    const MISSING_REPLACEMENT = '-99';

    /**
     * @param string $type
     * @return array
     */
    protected static function getInstructionFilePedFileToModelHeader($type)
    {
        return [
            ' ',
            'PEDFILE   '.MixBlupSetting::PEDIGREE_FILENAME_PREFIX.$type.'.txt',
            ' ID        I ', //PrimaryKey
            ' Vader     I ', //PrimaryKey Father
            ' Moeder    I ', //PrimaryKey Mother
            ' Bedrijf   I ',//ubn of birth, NOTE it is an integer here
            ' ',
            'PARFILE  '.MixBlupSetting::PARFILE_FILENAME,
            ' ',
            'MODEL',
        ];
    }


    /**
     * @return array
     */
    protected static function getInstructionFileEnding()
    {
        return [
            ' ',
            'SOLVING',
        ];
    }


    /**
     * @return array
     */
    protected static function getStandardizedBreedCodePartsAndHetRecOfInstructionFile()
    {
        return [
            ' CovTE      R #rasdelen TE, BT en DK, ze zijn genetisch identiek',  //TE, BT, DK are genetically all the same
            ' CovCF      R #rasdeel Clun Forest',  //Clun Forest
            ' CovBM      R #rasdeel Bleu du Maine',  //Bleu du Maine
            ' CovSW      R #rasdeel Swifter',  //Swifter
            ' CovNH      R #rasdeel Noordhollander',  //Noordhollander
            ' CovFL      R #rasdeel Flevolander',  //Flevolander
            ' CovHD      R #rasdeel Hampshire Down',  //Hampshire Down
            ' CovOV      R #overige rasdelen',  //other  (NN means unknown, also include it here)
            ' CovHet     T #Heterosis van het dier',
            ' CovRec     T #Recombinatie van het dier',
        ];
    }


    /**
     * Note! These values should match the values in getStandardizedBreedCodePartsAndHetRecOfInstructionFile()
     * Except for CovTE which is intentionally omitted to prevent entanglement during the MixBlup analyses, which slows down the process.
     *
     * @return string
     */
    protected static function getBreedCodesModel()
    {
        return 'CovCF CovBM CovSW CovNH CovFL CovHD CovOV CovHet CovRec';
    }

}