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
        ];

        if(MixBlupSetting::INCLUDE_ULNS) {
            $start[] = ' ULN        A';  //uln
        }

        $start[] = ' ID         I';  //primaryKey
        $start[] = ' Sekse      A';  //ram/ooi/N_B
        $start[] = ' JaarBedr   A #jaar en ubn van geboorte'; //year and ubn of birth
        $start[] = ' WorpID     A ';  //ulnMother._.lpad(litterOrdinal, with zeroes)
        $start[] = ' Inspectr   A #Code van NSFO Inspecteur';  //example: NSFO001 until NSFO999


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
            ' Bedrijf    I #ubn van geboorte', //ubn of birth
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
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getLegWorkModel($includeCommentedOutBreedValues = true)
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $baseModel = [
            'BeenwVGm' =>   ' BeenwVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM G(ID)',
            'BeenwDF' =>    ' BeenwDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];

        $extraModel = [
            'LinStVb' =>    ' '.$commentHashTag.'LinStVb',
            'LinZijStAb' => ' '.$commentHashTag.'LinZijStAb',
            'LinAchtStAb' => ' '.$commentHashTag.'LinAchtStAb',
            'LinPijpOmv' => ' '.$commentHashTag.'LinPijpOmv',
        ];

        return self::getModel($baseModel, $extraModel, $includeCommentedOutBreedValues);
    }


    /**
     * @return array
     */
    public static function generateLegWorkInstructionFile()
    {
        return self::generateExteriorInstructionFile(self::getLegWorkModel(), 'Beenwerk');
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getMuscularityModel($includeCommentedOutBreedValues = true)
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $baseModel = [
            'BespVGv' =>    ' BespVGv  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            'BespVGm' =>    ' BespVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM G(ID)',
            'BespDF'  =>    ' BespDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM G(ID)',
        ];

        $extraModel = [
            'LinVoorh' =>   ' '.$commentHashTag.'LinVoorh',
            'LinRugBr' =>   ' '.$commentHashTag.'LinRugBr',
            'LinRondBil' => ' '.$commentHashTag.'LinRondBil',
        ];

        return self::getModel($baseModel, $extraModel, $includeCommentedOutBreedValues);
    }


    /**
     * @return array
     */
    public static function generateMuscularityInstructionFile()
    {
        return self::generateExteriorInstructionFile(self::getMuscularityModel(), 'Bespiering');
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getProportionModel($includeCommentedOutBreedValues = true)
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $baseModel = [
            'EvenrVGm' =>   ' EvenrVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM G(ID)',
            'EvenrDF'  =>   ' EvenrDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];

        $extraModel = [
            'LinRugLen' =>  ' '.$commentHashTag.'LinRugLen',
            'LinKruis'  =>  ' '.$commentHashTag.'LinKruis',
        ];

        return self::getModel($baseModel, $extraModel, $includeCommentedOutBreedValues);
    }


    /**
     * @return array
     */
    public static function generateProportionInstructionFile()
    {
        return self::generateExteriorInstructionFile(self::getProportionModel(), 'Evenredigheid');
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getSkullModel($includeCommentedOutBreedValues = true)
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $baseModel = [
            'KopVGm' => ' KopVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
            'KopDF' =>  ' KopDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];

        $extraModel = [
            'LinKop' => ' '.$commentHashTag.'LinKop',
        ];

        return self::getModel($baseModel, $extraModel, $includeCommentedOutBreedValues);
    }


    /**
     * @return array
     */
    public static function generateSkullInstructionFile()
    {
        return self::generateExteriorInstructionFile(self::getSkullModel(), 'Kop');
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getProgressModel($includeCommentedOutBreedValues = true)
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $baseModel = [
            'OntwVGm' =>    ' OntwVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM G(ID)',
            'OntwDF' =>     ' OntwDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];

        $extraModel = [
            'LinRugLen' =>  ' '.$commentHashTag.'LinRugLen',
            'LinKruis' =>   ' '.$commentHashTag.'LinKruis',
        ];

        return self::getModel($baseModel, $extraModel, $includeCommentedOutBreedValues);
    }


    /**
     * @return array
     */
    public static function generateProgressInstructionFile()
    {
        return self::generateExteriorInstructionFile(self::getProgressModel(), 'Ontwikkeling');
    }


    /**
     * @param bool $includeCommentedOutBreedValues
     * @return array
     */
    public static function getExteriorTypeModel($includeCommentedOutBreedValues = true)
    {
        $commentHashTag = MixBlupSetting::INCLUDE_EXTERIOR_LINEAR_MEASUREMENTS ? '' : '# ';

        $baseModel = [
            'TypeVGm' =>    ' TypeVGm  ~ JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM G(ID)',
            'TypeDF' =>     ' TypeDF   ~ Sekse JaarBedr Inspectr '.self::getBreedCodesModel().' !RANDOM WorpID G(ID)',
        ];

        $extraModel = [
        ];

        return self::getModel($baseModel, $extraModel, $includeCommentedOutBreedValues);
    }


    /**
     * @return array
     */
    public static function generateExteriorTypeInstructionFile()
    {
        return self::generateExteriorInstructionFile(self::getExteriorTypeModel(), 'Type');
    }


}