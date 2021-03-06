<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;

/**
 * Class ReproductionInstructionFiles
 * @package AppBundle\MixBlup
 */
class ReproductionInstructionFiles extends MixBlupInstructionFileBase implements MixBlupInstructionFileInterface
{
    const TITLE_FERTILITY = 'Vruchtbaarheid';
    const TITLE_BIRTH_PROGRESS = 'Geboorteverloop';
    const INCLUDE_COMMENTED_OUT_TRAITS = true;

    /**
     * @inheritDoc
     */
    static function generateInstructionFiles()
    {
        return [
            MixBlupInstructionFile::BIRTH_PROGRESS => self::generateBirthProgressInstructionFile(),
            //FERTILITY exceeds 1 million calculations, so we split it up in 4 parts.
            MixBlupInstructionFile::FERTILITY_1 => self::generateFertilityInstructionFile(1),
            MixBlupInstructionFile::FERTILITY_2 => self::generateFertilityInstructionFile(2),
            MixBlupInstructionFile::FERTILITY_3 => self::generateFertilityInstructionFile(3),
            MixBlupInstructionFile::FERTILITY_4 => self::generateFertilityInstructionFile(4),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::BIRTH_PROGRESS) => self::generateBirthProgressRelaniInstructionFile(),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::FERTILITY_1) => self::generateFertilityRelaniInstructionFile(1),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::FERTILITY_2) => self::generateFertilityRelaniInstructionFile(2),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::FERTILITY_3) => self::generateFertilityRelaniInstructionFile(3),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::FERTILITY_4) => self::generateFertilityRelaniInstructionFile(4),
        ];
    }


    /**
     * @param array $model
     * @param string $titleType
     * @param boolean $isRelani
     * @return array
     */
    private static function reproductionInstructionFileBase(array $model = [], $titleType, $isRelani = false)
    {
        $start = [
            'TITLE '.$titleType,
            ' ',
            'DATAFILE  '.MixBlupFileName::getFertilityDataFileName().' !MISSING '.self::MISSING_REPLACEMENT,
        ];

        if(MixBlupSetting::INCLUDE_ULNS) {
            $start[] = ' ULN        A';  //uln of Ewe/Mother or lamb
        }

        $start[] = ' ID         I #id van ooi/moeder of lam';  //primaryKey of Ewe/Mother or lamb
        $start[] = ' Leeft      I #leeftijd ooi in jaren'; //age of ewe in years
        $start[] = ' Sekse      A';  //ram/ooi/N_B
        $start[] = ' JaarBedr   A #jaar en ubn van geboorte'; //year and ubn of birth


        $middle = [
            ' CovHetLam  R #Heterosis lam of worp', //Heterosis of offspring/litter
            ' CovRecLam  R #Recombinatie lam of worp', //Recombination of offspring/litter
            ' CovTE_M    R #Rasdeel TE van moeder', //BreedCode part TE of mother
            ' Inductie   R #Bronstinduction 1=Ja, 0=Nee', //pmsg value in mate 0=FALSE, 1=TRUE
            ' PermMil    I #Permanent milieu is identiek aan de ID van de ooi of moeder',
            ' IDM        I #Het unieke diernummer van de moeder van dier in hoofdkolom',
            ' WorpID     A ',
            ' TotGeb     T #Totaal geboren lammeren in de worp', //bornAliveCount in litter
            ' DoodGeb    T #Doodgeboren lammeren in de worp', //stillbornCount in litter
            ' Vroeg      T #Geworpen in eerste levensjaar', //GaveBirthAsOneYearOld 0=FALSE, 1=TRUE
            ' GewGeb     R #Geboortegewicht', //birthWeight
            ' GebGemak   T #Geboortegemak', //birthProgress from 0 to 4
            ' DrTijd     R #Draagtijd', //gestationPeriod in litter
            ' TusLamT    T #Tussenlamtijd', //birthInterval in litter
            ' Bedrijf    I '.self::getBlockString($isRelani).'#ubn van geboorte',  //ubn of birth
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middle,
            self::getInstructionFilePedFileToModelHeader(MixBlupType::FERTILITY, $isRelani),
            $model,
            self::getInstructionFileEnding($isRelani)
        ]);
    }


    /**
     * @param bool $includeCommentedOutBreedValues variable is included to match structure of other get...Model functions
     * @param bool $isRelani
     * @param bool $appendIdmBreedValues
     * @return array
     */
    public static function getBirthProgressModel($includeCommentedOutBreedValues = true, $isRelani = false, $appendIdmBreedValues = false)
    {
        $gebGemakSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' CovHetLam CovRecLam';

        $type = BreedValueTypeConstant::BIRTH_PROGRESS;
        $instruction = ' '.$type.'  ~ '.self::jaarBedrijf($isRelani).$gebGemakSolaniTraits.' !RANDOM G(ID,IDM)';

        $baseModel = [
            $type =>   $instruction
        ];

        if($appendIdmBreedValues) {
            $appendedModel = [
                $type.self::INDIRECT_SUFFIX =>   $instruction
            ];
            return ArrayUtil::concatArrayValues([$baseModel, $appendedModel], false);
        }

        return $baseModel;
    }


    /**
     * @param bool $isRelani
     * @return array
     */
    public static function getIndirectProgressModel($isRelani = true)
    {
        $type = BreedValueTypeConstant::BIRTH_PROGRESS;
        $gebGemakSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' CovHetLam CovRecLam';
        return [$type.self::INDIRECT_SUFFIX =>   ' '.$type.'  ~ '.self::jaarBedrijf($isRelani).$gebGemakSolaniTraits.' !RANDOM G(ID,IDM)'];
    }


    /**
     * @return array
     */
    public static function generateBirthProgressInstructionFile()
    {
        return self::reproductionInstructionFileBase(
            self::getBirthProgressModel(self::INCLUDE_COMMENTED_OUT_TRAITS),
            self::TITLE_BIRTH_PROGRESS);
    }


    /**
     * @return array
     */
    public static function generateBirthProgressRelaniInstructionFile()
    {
        return self::reproductionInstructionFileBase(
            self::getBirthProgressModel(self::INCLUDE_COMMENTED_OUT_TRAITS, true),
            self::TITLE_BIRTH_PROGRESS, true);
    }


    /**
     * @param int $part
     * @param bool $includeCommentedOutBreedValues
     * @param bool $isRelani
     * @return array
     */
    public static function getFertilityModel($part, $includeCommentedOutBreedValues = true, $isRelani = false)
    {
        $jaarBedr = self::jaarBedrijf($isRelani);

        $includeTotalBirths = $part == 1 || $part == null ? '' : ' #';
        $includeStillBirths = $part == 2 || $part == null ? '' : ' #';
        $includeEarlyFertility = $part == 3 || $part == null ? '' : ' #';
        $includeBirthInterval = $part == 4 || $part == null ? '' : ' #';

        $totGebModelSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Inductie Leeft CovHetLam CovRecLam';
        $doodGebModelSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel().' Inductie Leeft CovHetLam CovRecLam';
        $vroegModelSolaniTraits = $isRelani ? '' : ' '.self::getBreedCodesModel();

        $totGebModel =  ' TotGeb  ~ '.$jaarBedr.$totGebModelSolaniTraits.' !RANDOM PermMil G(ID)';
        $doodGebModel = ' DoodGeb ~ '.$jaarBedr.$doodGebModelSolaniTraits.' !RANDOM PermMil G(ID)';
        $vroegModel =   ' Vroeg   ~ '.$jaarBedr.$vroegModelSolaniTraits.' !RANDOM G(ID)';
        $tusLamTModel = ' TusLamT ~ '.$jaarBedr.' Leeft !RANDOM PermMil G(ID)';

        if($includeCommentedOutBreedValues) {
            return [
                'TotGeb' =>     $includeTotalBirths.$totGebModel,
                'DoodGeb' =>    $includeStillBirths.$doodGebModel,
                'Vroeg' =>      $includeEarlyFertility.$vroegModel,
                'TusLamT' =>    $includeBirthInterval.$tusLamTModel,
            ];

        } else {
            switch ($part) {
                case 1: return ['TotGeb' => $totGebModel];
                case 2: return ['DoodGeb' => $doodGebModel];
                case 3: return ['Vroeg' => $vroegModel];
                case 4: return ['TusLamT' => $doodGebModel];
                default: return [];
            }
        }
    }


    /**
     * @param int $part
     * @return array
     */
    public static function generateFertilityInstructionFile($part = null)
    {
        return self::reproductionInstructionFileBase(
            self::getFertilityModel($part,self::INCLUDE_COMMENTED_OUT_TRAITS),
            self::TITLE_FERTILITY);
    }


    /**
     * @param int $part
     * @return array
     */
    public static function generateFertilityRelaniInstructionFile($part = null)
    {
        return self::reproductionInstructionFileBase(
            self::getFertilityModel($part,self::INCLUDE_COMMENTED_OUT_TRAITS, true),
            self::TITLE_FERTILITY, true);
    }


}
