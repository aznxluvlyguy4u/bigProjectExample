<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;

/**
 * Class LambMeatIndexInstructionFiles
 * @package AppBundle\MixBlup
 */
class LambMeatIndexInstructionFiles extends MixBlupInstructionFileBase implements MixBlupInstructionFileInterface
{

    /**
     * @inheritDoc
     */
    static function generateInstructionFiles()
    {
        return [
            MixBlupInstructionFile::LAMB_MEAT => self::generateLambMeatInstructionFile(),
            MixBlupInstructionFile::TAIL_LENGTH => self::generateTailLengthInstructionFile(),
        ];
    }


    /**
     * @param array $customRecords
     * @param string $fileType
     * @return array
     */
    private static function generateTestAttributeInstructionFile(array $customRecords = [], $fileType = 'vleeslamkenmerken')
    {
        $start = [
            'TITEL   schapen fokwaarde berekening '.$fileType,
            'DATAFILE  '.MixBlupSetting::DATA_FILENAME_PREFIX.MixBlupSetting::LAMB_MEAT_INDEX.'.txt',
            ' ID         A !missing '.MixBlupNullFiller::ULN.' #uln',  //uln
            ' IDM        A !missing '.MixBlupNullFiller::ULN.' #uln van moeder',  //uln of mother
            ' JaarBedr   A !missing '.MixBlupNullFiller::GROUP.' #jaar en ubn van geboorte', //year and ubn of birth
            ' Sekse      A !missing '.MixBlupNullFiller::GENDER,  //ram/ooi/N_B
            ' WorpID     A !missing '.MixBlupNullFiller::GROUP.' #worpnummer',  //ulnMother._.lpad(litterOrdinal, with zeroes)
        ];

        $middle = [
            ' Nling      I !missing '.MixBlupNullFiller::COUNT.' #worpgrootte',
            ' Zling      I !missing '.MixBlupNullFiller::COUNT.' #zooggrootte',
            ' LeeftScan  I !missing '.MixBlupNullFiller::COUNT.' #op moment van meting in dagen', //age of animal on measurementDate in days
            ' GewScan    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #gewicht bij scannen',   //weight at measurementDate
        ];

        if($customRecords == []) {
            //Insert all records as default
            $customRecords = [
                ' GewGeb     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #geboortegewicht',   //weight at birth
                ' StaartLen  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #staartlengte', //tailLength
                ' Gew08wk    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #8 weken gewichtmeting', //weight measurement at 8 weeks
                ' Gew20wk    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #20 weken gewichtmeting', //weight measurement at 20 weeks
                ' Vetd01     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE,
                ' Vetd02     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE,
                ' Vetd03     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE,
                ' Spierd     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #spierdikte',
            ];
        }

        $lastDataRecords = [
            ' Bedrijf    I !missing '.MixBlupNullFiller::UBN.' #ubn van geboorte',
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middle,
            $customRecords,
            $lastDataRecords,
            self::getInstructionFileDefaultEnding()
        ]);
    }


    /**
     * @return array
     */
    public static function generateLambMeatInstructionFile()
    {
        $customRecords = [
            ' GewGeb     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #geboortegewicht',   //weight at birth
            ' Gew08wk    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #8 weken gewichtmeting', //weight measurement at 8 weeks
            ' Gew20wk    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #20 weken gewichtmeting', //weight measurement at 20 weeks
            ' Vetd01     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE,
            ' Vetd02     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE,
            ' Vetd03     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE,
            ' Spierd     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #spierdikte',
        ];
        return self::generateTestAttributeInstructionFile($customRecords, 'vleeslamkenmerken');
    }


    /**
     * @return array
     */
    public static function generateTailLengthInstructionFile()
    {
        $customRecords = [
            ' StaartLen  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #staartlengte', //tailLength
        ];
        return self::generateTestAttributeInstructionFile($customRecords, 'staartlengte');
    }


}