<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Enumerator\MixBlupType;
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
     * @param array $model
     * @param string $fileType
     * @return array
     */
    private static function generateTestAttributeInstructionFile(array $model, $fileType)
    {
        $start = [
            'TITLE '.$fileType,
            ' ',
            'DATAFILE  '.MixBlupFileName::getLambMeatIndexDataFileName().' !MISSING '.self::MISSING_REPLACEMENT,
        ];

        if(MixBlupSetting::INCLUDE_ULNS) {
            $start[] = ' ULN        A';  //uln
        }

        $start[] = ' ID         I';  //primaryKey
        $start[] = ' IDM        I #id of mother';  //primaryKey of mother
        $start[] = ' JaarBedr   A #jaar en ubn van geboorte'; //year and ubn of birth
        $start[] = ' Sekse      A ';  //ram/ooi/N_B
        $start[] = ' WorpID     A ';  //ulnMother._.lpad(litterOrdinal, with zeroes)


        $middle = [
            ' Nling      I #worpgrootte',
            ' Zling      I #zooggrootte',
            ' LeeftScan  R #leeftijd op moment van scanmeting in dagen', //age of animal on measurementDate in days
            ' GewScan    R #gewicht bij scannen',   //weight at measurementDate
            ' GewGeb     T #geboortegewicht',   //weight at birth
            ' StaartLen  T #staartlengte', //tailLength
            ' Gew08      T #8 weken gewichtmeting', //weight measurement at 8 weeks
            ' Leeft08    R #leeftijd op moment van 8 weken gewichtmeting in dagen', //age of animal on measurementDate weight 8 weeks in days
            ' Gew20      T #20 weken gewichtmeting', //weight measurement at 20 weeks
            ' Leeft20    R #leeftijd op moment van 20 weken gewichtmeting in dagen', //age of animal on measurementDate weight 20 weeks in days
            ' Vetd01     T',
            ' Vetd02     T',
            ' Vetd03     T',
            ' Spierd     T', #spierdikte',
            ' Bedrijf    I #ubn van geboorte', //ubn of birth
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middle,
            self::getInstructionFilePedFileToModelHeader(MixBlupType::LAMB_MEAT_INDEX),
            $model,
            self::getInstructionFileEnding(),
        ]);
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getLambMeatModel($includeCommentedOutBreedValues = true)
    {
        $base1 = [
            'GewGeb' => ' GewGeb    ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling !RANDOM WorpID G(ID,IDM)',
        ];

        $commentedOut1 = [
            'Gew08' =>  '# Gew08     ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling LeeftScan !RANDOM WorpID G(ID)',
        ];

        $base2 = [
            'Gew20' =>  ' Gew20     ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling LeeftScan !RANDOM WorpID G(ID)',
            'Vetd01' => ' Vetd01    ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling GewScan !RANDOM WorpID G(ID)',
        ];

        $commentedOut2 = [
            'Vetd02' => '# Vetd02    ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling GewScan !RANDOM WorpID G(ID)',
        ];

        $base3 = [
            'Vetd03' => ' Vetd03    ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling GewScan !RANDOM WorpID G(ID)',
            'Spierd' => ' Spierd    ~ JaarBedr '.self::getBreedCodesModel().' Sekse Nling GewScan !RANDOM WorpID G(ID)',
        ];


        if ($includeCommentedOutBreedValues) {
            $models = [
                $base1,
                $commentedOut1,
                $base2,
                $commentedOut2,
                $base3,
            ];
        } else {
            $models = [
                $base1,
                $base2,
                $base3,
            ];
        }

        return ArrayUtil::concatArrayValues($models, false);
    }


    /**
     * @return array
     */
    public static function generateLambMeatInstructionFile()
    {
        return self::generateTestAttributeInstructionFile(self::getLambMeatModel(), 'Vleeslamkenmerken');
    }


    /**
     * @param bool $includeCommentedOutBreedValues variable is included to match structure of other get...Model functions
     * @return array
     */
    public static function getTailLengthModel($includeCommentedOutBreedValues = true)
    {
        return $baseModel = [
            'StaartLen' => ' StaartLen ~ GewGeb JaarBedr Nling Sekse '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];
    }


    /**
     * @return array
     */
    public static function generateTailLengthInstructionFile()
    {
        return self::generateTestAttributeInstructionFile(self::getTailLengthModel(), 'Staartlengte');
    }


}