<?php


namespace AppBundle\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Enumerator\MixBlupType;
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
     * @param array $model
     * @param string $fileType
     * @return array
     */
    private static function generateExteriorInstructionFile($model, $fileType)
    {
        $start = [
            'TITLE   Exterieur: '.$fileType,
            ' ',
            'DATAFILE  '.MixBlupFileName::getExteriorDataFileName().' !MISSING '.self::MISSING_REPLACEMENT,
            ' ID         A #uln',  //uln
            ' Sekse      A',  //ram/ooi/N_B
            ' JaarBedr   A #jaar en ubn van geboorte', //year and ubn of birth
            ' WorpID     A ',  //ulnMother._.lpad(litterOrdinal, with zeroes)
            ' Inspectr   A #Code van NSFO Inspecteur',  //example: NSFO001 until NSFO999
        ];

        $exteriorMeasurements = [
            ' BespVGv    T #BESPIERING, EXTKIND=VG en sekse dier is ooi',
            ' KopVGm     T #KOP, EXTKIND=VG en sekse dier is ram',
            ' OntwVGm    T #ONTWIKKELING, EXTKIND=VG en sekse dier is ram',
            ' BespVGm    T #BESPIERING, EXTKIND=VG en sekse dier is ram',
            ' EvenrVGm   T #EVENREDIGHEID, EXTKIND=VG en sekse dier is ram',
            ' TypeVGm    T #TYPE, EXTKIND=VG en sekse dier is ram',
            ' BeenwVGm   T #BEENWERK, EXTKIND=VG en sekse dier is ram',
            ' VachtVGm   T #VACHT, EXTKIND=VG en sekse dier is ram',
            ' AlgVkVGm   T #ALGEMEEN_VOORKOMEN, EXTKIND=VG en sekse dier is ram',
            ' SchoftVGm  T #SCHOFTHOOGTE, EXTKIND=VG en sekse dier is ram, mogen decimalen bevatten',
            ' BorstdVGm  T #BORSTDIEPTE, EXTKIND=VG en sekse dier is ram, mogen decimalen bevatten',
            ' RomplVGm   T #ROMPLENGTE, EXTKIND=VG en sekse dier is ram, mogen decimalen bevatten',
            ' KopDF      T #KOP, EXTKIND=DD/DF/HK',
            ' OntwDF     T #ONTWIKKELING, EXTKIND=DD/DF/HK',
            ' BespDF     T #BESPIERING, EXTKIND=DD/DF/HK',
            ' EvenrDF    T #EVENREDIGHEID, EXTKIND=DD/DF/HK',
            ' TypeDF     T #TYPE, EXTKIND=DD/DF/HK',
            ' BeenwDF    T #BEENWERK, EXTKIND=DD/DF/HK',
            ' VachtDF    T #VACHT, EXTKIND=DD/DF/HK',
            ' AlgVkDF    T #ALGEMEEN_VOORKOMEN, EXTKIND=DD/DF/HK',
            ' SchofthDF  T #SCHOFTHOOGTE, EXTKIND=DD/DF/HK, mogen decimalen bevatten',
            ' BorstdDF   T #BORSTDIEPTE, EXTKIND=DD/DF/HK, mogen decimalen bevatten',
            ' RomplDF    T #ROMPLENGTE, EXTKIND=DD/DF/HK, mogen decimalen bevatten',
        ];

        $exteriorLinearMeasurements = [
            ' LinKop     T #KOP_LINEAR, Lineair',
            ' LinVoorh   T #VOORHAND, Lineair',
            ' LinRugLen  T #RUGLENGTE, Lineair',
            ' LinRugBr   T #RUGBREEDTE, Lineair',
            ' LinKruis   T #KRUIS, Lineair',
            ' LinRondBil T #RONDING_BIL, Lineair',
            ' LinStVb    T #STAND_VOORBENEN, Lineair',
            ' LinZijStAb T #STAND_ZIJAANZICHT_ACHTERBENEN, Lineair',
            ' LinAchtStAb T #STAND_ACHTERAANZICHT_ACHTERBENEN, Lineair',
            ' LinPijpOmv T #PIJP_OMVANG, Lineair',
            ' InspLin    A #Code NSFO INSPECTEUR_LINEAR',
        ];

        $lastDataRecords = [
            ' Bedrijf    I #ubn van geboorte',
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $exteriorMeasurements,
            $exteriorLinearMeasurements,
            $lastDataRecords,
            self::getInstructionFilePedFileToModelHeader(MixBlupType::EXTERIOR),
            $model,
            self::getInstructionFileEnding(),
        ]);
    }


    /**
     * @return array
     */
    public static function generateLegWorkInstructionFile()
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

            $model = [
            ' BeenwVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' BeenwDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' '.$commentHashTag.'LinStVb',
            ' '.$commentHashTag.'LinZijStAb',
            ' '.$commentHashTag.'LinAchtStAb',
            ' '.$commentHashTag.'LinPijpOmv',
        ];

        return self::generateExteriorInstructionFile($model, 'Beenwerk');
    }


    /**
     * @return array
     */
    public static function generateMuscularityInstructionFile()
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $model = [
            ' BespVGv  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' BespVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' BespDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' '.$commentHashTag.'LinVoorh',
            ' '.$commentHashTag.'LinRugBr',
            ' '.$commentHashTag.'LinRondBil',
        ];

        return self::generateExteriorInstructionFile($model, 'Bespiering');
    }


    /**
     * @return array
     */
    public static function generateProportionInstructionFile()
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $model = [
            ' EvenrVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' EvenrDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' '.$commentHashTag.'LinRugLen',
            ' '.$commentHashTag.'LinKruis',
        ];

        return self::generateExteriorInstructionFile($model, 'Evenredigheid');
    }


    /**
     * @return array
     */
    public static function generateSkullInstructionFile()
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $model = [
            ' KopVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' KopDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' '.$commentHashTag.'LinKop',
        ];

        return self::generateExteriorInstructionFile($model, 'Kop');
    }


    /**
     * @return array
     */
    public static function generateProgressInstructionFile()
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $model = [
            ' OntwVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' OntwDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' '.$commentHashTag.'LinRugLen',
            ' '.$commentHashTag.'LinKruis',
        ];

        return self::generateExteriorInstructionFile($model, 'Ontwikkeling');
    }


    /**
     * @return array
     */
    public static function generateExteriorTypeInstructionFile()
    {
        $model = [
            ' TypeVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            ' TypeDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];

        return self::generateExteriorInstructionFile($model, 'Type');
    }


}