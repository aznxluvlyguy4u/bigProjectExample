<?php


namespace AppBundle\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;

/**
 * Class InstructionFilesExterior
 * @package AppBundle\MixBlup
 */
class ExteriorInstructionFiles extends MixBlupInstructionFileBase implements MixBlupInstructionFileInterface
{
    /**
     * @inheritDoc
     */
    static function generateInstructionFiles()
    {
        return [
            MixBlupInstructionFile::EXTERIOR_LEG_WORK => self::generateLegWorkInstructionFile(),
            MixBlupInstructionFile::EXTERIOR_MUSCULARITY => self::generateMuscularityInstructionFile(),
            MixBlupInstructionFile::EXTERIOR_PROPORTION => self::generateProportionInstructionFile(),
            MixBlupInstructionFile::EXTERIOR_SKULL => self::generateSkullInstructionFile(),
            MixBlupInstructionFile::EXTERIOR_PROGRESS => self::generateProgressInstructionFile(),
            MixBlupInstructionFile::EXTERIOR_TYPE => self::generateExteriorTypeInstructionFile(),
        ];
    }

    /**
     * @param array $exteriorMeasurements
     * @param array $exteriorLinearMeasurements
     * @param string $fileType
     * @return array
     */
    private static function generateExteriorInstructionFile($exteriorMeasurements = null,
                                                     $exteriorLinearMeasurements = null, $fileType = null)
    {
        $fileType = $fileType == null ? 'exterieurkenmerken' : 'exterieurkenmerk '.$fileType;

        $start = [
            'TITEL   schapen fokwaarde berekening '.$fileType,
            'DATAFILE  '.MixBlupSetting::DATA_FILENAME_PREFIX.MixBlupSetting::EXTERIOR.'.txt',
            ' ID         A !missing '.MixBlupNullFiller::ULN.' #uln',  //uln
            ' Sekse      A !missing '.MixBlupNullFiller::GENDER,  //ram/ooi/N_B
            ' JaarBedr   A !missing '.MixBlupNullFiller::GROUP.' #jaar en ubn van geboorte', //year and ubn of birth
            ' Inspectr   A !missing '.MixBlupNullFiller::CODE.' #Code van NSFO Inspecteur',  //example: NSFO001 until NSFO999
        ];

        if($exteriorMeasurements == null) {
            $exteriorMeasurements = [
                ' BespVGv    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BESPIERING, EXTKIND=VG en sekse dier is ooi',
                ' KopVGm     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KOP, EXTKIND=VG en sekse dier is ram',
                ' OntwVGm    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ONTWIKKELING, EXTKIND=VG en sekse dier is ram',
                ' BespVGm    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BESPIERING, EXTKIND=VG en sekse dier is ram',
                ' EvenrVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #EVENREDIGHEID, EXTKIND=VG en sekse dier is ram',
                ' TypeVGm    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #TYPE, EXTKIND=VG en sekse dier is ram',
                ' BeenwVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BEENWERK, EXTKIND=VG en sekse dier is ram',
                ' VachtVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #VACHT, EXTKIND=VG en sekse dier is ram',
                ' AlgVkVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ALGEMEEN_VOORKOMEN, EXTKIND=VG en sekse dier is ram',
                ' SchoftVGm  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #SCHOFTHOOGTE, EXTKIND=VG en sekse dier is ram',
                ' BorstdVGm  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BORSTDIEPTE, EXTKIND=VG en sekse dier is ram',
                ' RomplVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ROMPLENGTE, EXTKIND=VG en sekse dier is ram',
                ' KopDF      T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KOP, EXTKIND=DD/DF/HK',
                ' OntwDF     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ONTWIKKELING, EXTKIND=DD/DF/HK',
                ' BespDF     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BESPIERING, EXTKIND=DD/DF/HK',
                ' EvenrDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #EVENREDIGHEID, EXTKIND=DD/DF/HK',
                ' TypeDF     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #TYPE, EXTKIND=DD/DF/HK',
                ' BeenwDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BEENWERK, EXTKIND=DD/DF/HK',
                ' VachtDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #VACHT, EXTKIND=DD/DF/HK',
                ' AlgVkDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ALGEMEEN_VOORKOMEN, EXTKIND=DD/DF/HK',
                ' SchofthDF  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #SCHOFTHOOGTE, EXTKIND=DD/DF/HK',
                ' BorstdDF   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BORSTDIEPTE, EXTKIND=DD/DF/HK',
                ' RomplDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ROMPLENGTE, EXTKIND=DD/DF/HK',
            ];
        }

        if($exteriorLinearMeasurements == null) {
            $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
                ' LinKop     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KOP_LINEAR, Lineair',
                ' LinVoorh   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #VOORHAND, Lineair',
                ' LinRugLen  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RUGLENGTE, Lineair',
                ' LinRugBr   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RUGBREEDTE, Lineair',
                ' LinKruis   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KRUIS, Lineair',
                ' LinRondBil T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RONDING_BIL, Lineair',
                ' LinStVb    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #STAND_VOORBENEN, Lineair',
                ' LinZijStAb T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #STAND_ZIJAANZICHT_ACHTERBENEN, Lineair',
                ' LinAchtStAb T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #STAND_ACHTERAANZICHT_ACHTERBENEN, Lineair',
                ' LinPijpOmv T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #PIJP_OMVANG, Lineair',
            ] : [];
        }

        $exteriorLinearMeasurementsInspector = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            ' InspLin    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #Code NSFO INSPECTEUR_LINEAR',
        ] : [];

        $lastDataRecords = [
            ' Bedrijf    I !missing '.MixBlupNullFiller::UBN.' #ubn van geboorte',
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $exteriorMeasurements,
            $exteriorLinearMeasurements,
            $exteriorLinearMeasurementsInspector,
            $lastDataRecords,
            self::getInstructionFileDefaultEnding()
        ]);
    }


    /**
     * @return array
     */
    public static function generateLegWorkInstructionFile()
    {
        $exteriorMeasurements = [
            ' BeenwVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BEENWERK, EXTKIND=VG en sekse dier is ram',
            ' BeenwDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BEENWERK, EXTKIND=DD/DF/HK',
        ];

        $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            ' LinStVb    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #STAND_VOORBENEN, Lineair',
            ' LinZijStAb T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #STAND_ZIJAANZICHT_ACHTERBENEN, Lineair',
            ' LinAchtStAb T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #STAND_ACHTERAANZICHT_ACHTERBENEN, Lineair',
            ' LinPijpOmv T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #PIJP_OMVANG, Lineair',
        ] : [];

        return self::generateExteriorInstructionFile($exteriorMeasurements, $exteriorLinearMeasurements, 'beenwerk');
    }


    /**
     * @return array
     */
    public static function generateMuscularityInstructionFile()
    {
        $exteriorMeasurements = [
            ' BespVGv    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BESPIERING, EXTKIND=VG en sekse dier is ooi',
            ' BespVGm    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BESPIERING, EXTKIND=VG en sekse dier is ram',
            ' BespDF     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #BESPIERING, EXTKIND=DD/DF/HK',
        ];

        $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            ' LinVoorh   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #VOORHAND, Lineair',
            ' LinRugBr   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RUGBREEDTE, Lineair',
            ' LinRondBil T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RONDING_BIL, Lineair',
        ] : [];

        return self::generateExteriorInstructionFile($exteriorMeasurements, $exteriorLinearMeasurements, 'bespiering');
    }


    /**
     * @return array
     */
    public static function generateProportionInstructionFile()
    {
        $exteriorMeasurements = [
            ' EvenrVGm   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #EVENREDIGHEID, EXTKIND=VG en sekse dier is ram',
            ' EvenrDF    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #EVENREDIGHEID, EXTKIND=DD/DF/HK',
        ];

        $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            ' LinRugLen  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RUGLENGTE, Lineair',
            ' LinKruis   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KRUIS, Lineair',
        ] : [];

        return self::generateExteriorInstructionFile($exteriorMeasurements, $exteriorLinearMeasurements, 'evenredigheid');
    }


    /**
     * @return array
     */
    public static function generateSkullInstructionFile()
    {
        $exteriorMeasurements = [
            ' KopVGm     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KOP, EXTKIND=VG en sekse dier is ram',
            ' KopDF      T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KOP, EXTKIND=DD/DF/HK',
        ];

        $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            ' LinKop     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KOP_LINEAR, Lineair',
        ] : [];

        return self::generateExteriorInstructionFile($exteriorMeasurements, $exteriorLinearMeasurements, 'kop');
    }


    /**
     * @return array
     */
    public static function generateProgressInstructionFile()
    {
        $exteriorMeasurements = [
            ' OntwVGm    T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ONTWIKKELING, EXTKIND=VG en sekse dier is ram',
            ' OntwDF     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #ONTWIKKELING, EXTKIND=DD/DF/HK',
        ];

        $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            ' LinRugLen  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #RUGLENGTE, Lineair',
            ' LinKruis   T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #KRUIS, Lineair',
        ] : [];

        return self::generateExteriorInstructionFile($exteriorMeasurements, $exteriorLinearMeasurements, 'ontwikkeling');
    }


    /**
     * @return array
     */
    public static function generateExteriorTypeInstructionFile()
    {
        $exteriorMeasurements = [
            ' TypeVGm    T !missing ' . MixBlupNullFiller::MEASUREMENT_VALUE . ' #TYPE, EXTKIND=VG en sekse dier is ram',
            ' TypeDF     T !missing ' . MixBlupNullFiller::MEASUREMENT_VALUE . ' #TYPE, EXTKIND=DD/DF/HK',
        ];

        $exteriorLinearMeasurements = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? [
            //Empty
        ] : [];

        return self::generateExteriorInstructionFile($exteriorMeasurements, $exteriorLinearMeasurements, 'type');
    }


}