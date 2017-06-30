<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupFolder;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;

/**
 * Class MixBlupInstructionFileBase
 * @package AppBundle\MixBlup
 */
abstract class MixBlupInstructionFileBase
{
    const MISSING_BLOCK_REPLACEMENT = 1;
    const MISSING_REPLACEMENT = '-99';
    const CONSTANT_MISSING_PARENT_REPLACEMENT = 0;
    const PEDIGREE_FILE_DATE_OF_BIRTH_NULL_REPLACEMENT = '1950-01-01'; //As long as the date is not included in the last 5 years.
    const INCLUDE_COMMENTED_OUT_TRAITS = true;
    const INDIRECT_SUFFIX = '_INDIRECT';


    /**
     * @param bool $isRelani
     * @return string
     */
    protected static function getBlockString($isRelani = false)
    {
        return $isRelani ? '!BLOCK ' : '';
    }


    /**
     * @param bool $isRelani
     * @return string
     */
    protected static function jaarBedrijf($isRelani = false)
    {
        return $isRelani ? 'BL(JaarBedr)' : 'JaarBedr';
    }

    /**
     * @param string $type
     * @param boolean $isRelani
     * @return array
     */
    protected static function getInstructionFilePedFileToModelHeader($type, $isRelani = false)
    {
        return [
            ' ',
            'PEDFILE   '.MixBlupSetting::PEDIGREE_FILENAME_PREFIX.$type.'.txt',
            ' ID        I ', //PrimaryKey
            ' Vader     I ', //PrimaryKey Father
            ' Moeder    I ', //PrimaryKey Mother
            ' Bedrijf   I '.self::getBlockString($isRelani),//ubn of birth, NOTE it is an integer here
            ' Sekse     A'. //ram/ooi/N_B
            ' GebDatum  A'. //dateOfBirth
            ' Uln       A'. //ULN
            ' ',
            'PARFILE  '.MixBlupSetting::PARFILE_FILENAME,
            ' ',
            'MODEL',
        ];
    }


    /**
     * @param boolean $isRelani
     * @return array
     */
    protected static function getInstructionFileEnding($isRelani = false)
    {
        $reliabilityString = $isRelani ? '!RELIABILITY' : '';

        return [
            ' ',
            'SOLVING',
            $reliabilityString,
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
            ' CovHet     R #Heterosis van het dier',
            ' CovRec     R #Recombinatie van het dier',
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



    /**
     * @param array $baseModel
     * @param array $extraModel
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    protected static function getModel($baseModel, $extraModel, $includeCommentedOutBreedValues = true)
    {
        if ($includeCommentedOutBreedValues) {
            return ArrayUtil::concatArrayValues([
                $baseModel,
                $extraModel,
            ], false);

        } else {
            return $baseModel;
        }
    }


}