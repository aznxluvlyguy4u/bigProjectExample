<?php


namespace AppBundle\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupSetting;

/**
 * Class MixBlupInstructionFileBase
 * @package AppBundle\MixBlup
 */
abstract class MixBlupInstructionFileBase
{

    /**
     * @return array
     */
    protected static function getInstructionFileDefaultEnding()
    {
        return [
            'PEDFILE   '.MixBlupFolder::PEDIGREE.'.txt',
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
    protected static function getStandardizedBreedCodePartsAndHetRecOfInstructionFile()
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
    
}