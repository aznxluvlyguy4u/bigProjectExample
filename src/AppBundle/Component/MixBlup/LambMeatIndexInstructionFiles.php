<?php


namespace AppBundle\Component\MixBlup;

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
            MixBlupInstructionFile::relani(MixBlupInstructionFile::LAMB_MEAT) => self::generateLambMeatRelaniInstructionFile(),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::TAIL_LENGTH) => self::generateTailLengthRelaniInstructionFile(),
        ];
    }


    /**
     * @param array $model
     * @param string $fileType
     * @param boolean $isRelani
     * @return array
     */
    private static function generateTestAttributeInstructionFile(array $model, $fileType, $isRelani = false)
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
            ' Bedrijf    I '.self::getBlockString($isRelani).'#ubn van geboorte', //ubn of birth
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middle,
            self::getInstructionFilePedFileToModelHeader(MixBlupType::LAMB_MEAT_INDEX, $isRelani),
            $model,
            self::getInstructionFileEnding($isRelani),
        ]);
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @param bool $isRelani
     * @param bool $appendIdmBreedValues
     * @return array
     */
    public static function getLambMeatModel($includeCommentedOutBreedValues = true, $isRelani = false, $appendIdmBreedValues = false)
    {
        $jaarBedr = self::jaarBedrijf($isRelani);

        $gewGebSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling';
        $gew08SolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling LeeftScan';
        $gew20SolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling LeeftScan';
        $vetd01SolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling GewScan';
        $vetd02SolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling GewScan';
        $vetd03SolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling GewScan';
        $spierdSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling GewScan';

        $base1 = [
            'GewGeb' => ' GewGeb    ~ '.$jaarBedr.$gewGebSolaniTraits.' !RANDOM WorpID G(ID,IDM)',
        ];


        $appendedBase1 = [
            'GewGeb'.self::INDIRECT_SUFFIX => ' GewGeb    ~ '.$jaarBedr.$gewGebSolaniTraits.' !RANDOM WorpID G(ID,IDM)',
        ];

        $commentedOut1 = [
            'Gew08' =>  '# Gew08     ~ '.$jaarBedr.$gew08SolaniTraits.' !RANDOM WorpID G(ID)',
        ];

        $base2 = [
            'Gew20' =>  ' Gew20     ~ '.$jaarBedr.$gew20SolaniTraits.' !RANDOM WorpID G(ID)',
            'Vetd01' => ' Vetd01    ~ '.$jaarBedr.$vetd01SolaniTraits.' !RANDOM WorpID G(ID)',
        ];

        $commentedOut2 = [
            'Vetd02' => '# Vetd02    ~ '.$jaarBedr.$vetd02SolaniTraits.' !RANDOM WorpID G(ID)',
        ];

        $base3 = [
            'Vetd03' => ' Vetd03    ~ '.$jaarBedr.$vetd03SolaniTraits.' !RANDOM WorpID G(ID)',
            'Spierd' => ' Spierd    ~ '.$jaarBedr.$spierdSolaniTraits.' !RANDOM WorpID G(ID)',
        ];


        if ($includeCommentedOutBreedValues) {
            if($appendIdmBreedValues) {
                $models = [
                    $base1,
                    $commentedOut1,
                    $base2,
                    $commentedOut2,
                    $base3,
                    $appendedBase1,
                ];
            } else {
                $models = [
                    $base1,
                    $commentedOut1,
                    $base2,
                    $commentedOut2,
                    $base3,
                ];
            }
        } else {
            if($appendIdmBreedValues) {
                $models = [
                    $base1,
                    $base2,
                    $base3,
                    $appendedBase1,
                ];
            } else {
                $models = [
                    $base1,
                    $base2,
                    $base3,
                ];
            }
        }

        return ArrayUtil::concatArrayValues($models, false);
    }


    /**
     * @param bool $isRelani
     * @return array
     */
    public static function getIndirectLambMeatModel($isRelani = true)
    {
        $jaarBedr = self::jaarBedrijf($isRelani);
        $gewGebSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Sekse Nling';

        return [
            'GewGeb'.self::INDIRECT_SUFFIX => ' GewGeb    ~ '.$jaarBedr.$gewGebSolaniTraits.' !RANDOM WorpID G(ID,IDM)',
        ];
    }


    /**
     * @return array
     */
    public static function generateLambMeatInstructionFile()
    {
        return self::generateTestAttributeInstructionFile(
            self::getLambMeatModel(self::INCLUDE_COMMENTED_OUT_TRAITS), 'Vleeslamkenmerken');
    }


    /**
     * @return array
     */
    public static function generateLambMeatRelaniInstructionFile()
    {
        return self::generateTestAttributeInstructionFile(
            self::getLambMeatModel(self::INCLUDE_COMMENTED_OUT_TRAITS, true), 'Vleeslamkenmerken', true);
    }


    /**
     * @param bool $includeCommentedOutBreedValues variable is included to match structure of other get...Model functions
     * @param bool $isRelani
     * @return array
     */
    public static function getTailLengthModel($includeCommentedOutBreedValues = true, $isRelani = false)
    {
        $staartLenSolaniTraits = $isRelani ? '' : ' GewGeb Nling Sekse '.self::getBreedCodesModel();

        return $baseModel = [
            'StaartLen' => ' StaartLen ~ '.self::jaarBedrijf($isRelani).$staartLenSolaniTraits.' !RANDOM WorpID G(ID)',
        ];
    }


    /**
     * @return array
     */
    public static function generateTailLengthInstructionFile()
    {
        return self::generateTestAttributeInstructionFile(
            self::getTailLengthModel(self::INCLUDE_COMMENTED_OUT_TRAITS), 'Staartlengte');
    }


    /**
     * @return array
     */
    public static function generateTailLengthRelaniInstructionFile()
    {
        return self::generateTestAttributeInstructionFile(
            self::getTailLengthModel(self::INCLUDE_COMMENTED_OUT_TRAITS, true), 'Staartlengte', true);
    }


}