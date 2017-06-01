<?php


namespace AppBundle\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
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

    /**
     * @inheritDoc
     */
    static function generateInstructionFiles()
    {
        return [
//            MixBlupInstructionFile::LITTER_SIZE => self::generateLitterSizeInstructionFile(), //NOT USED AT THE MOMENT
            MixBlupInstructionFile::BIRTH_PROGRESS => self::generateBirthProcessInstructionFile(),
            MixBlupInstructionFile::FERTILITY => self::generateFertilityInstructionFile(),
        ];
    }


    /**
     * @param array $model
     * @param string $titleType
     * @return array
     */
    private static function reproductionInstructionFileBase(array $model = [], $titleType)
    {
        $start = [
            'TITLE '.$titleType,
            ' ',
            'DATAFILE  '.MixBlupFileName::getFertilityDataFileName().' !MISSING '.self::MISSING_REPLACEMENT,
            ' ULN        A #uln van ooi/moeder of lam',  //uln of Ewe/Mother or lamb
            ' ID         I #id van ooi/moeder of lam',  //primaryKey of Ewe/Mother or lamb
            ' Leeft      I #leeftijd ooi in jaren', //age of ewe in years
            ' Sekse      A ',  //ram/ooi/N_B
            ' JaarBedr   A #jaar en ubn van geboorte', //year and ubn of birth
        ];

        $middle = [
            ' CovHetLam  R #Heterosis lam of worp', //Heterosis of offspring/litter
            ' CovRecLam  R #Recombinatie lam of worp', //Recombination of offspring/litter
            ' CovTE_M    R #Rasdeel TE van moeder', //BreedCode part TE of mother //TODO definition still unclear
            ' Inductie   I #Bronstinduction 0=Ja, 1=Nee', //pmsg value in mate 0=FALSE, 1=TRUE
            ' PermMil    I #Permanent milieu is identiek aan de ID van de ooi of moeder',
            ' IDM        I #Het unieke diernummer van de moeder', //TODO definition still unclear
            ' WorpID     A ',
            ' TotGeb     T #Totaal geboren lammeren in de worp', //bornAliveCount in litter
            ' DoodGeb    T #Doodgeboren lammeren in de worp', //stillbornCount in litter
            ' Vroeg      T #Geworpen in eerste levensjaar', //GaveBirthAsOneYearOld 0=FALSE, 1=TRUE
            ' GewGeb     R #Geboortegewicht', //birthWeight
            ' GebGemak   T #Geboortegemak', //birthProgress from 0 to 4
            ' DrTijd     R #Draagtijd', //gestationPeriod in litter
            ' TusLamT    T #Tussenlamtijd', //birthInterval in litter
            ' Bedrijf    I #ubn van geboorte',  //ubn of birth
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middle,
            self::getInstructionFilePedFileToModelHeader(MixBlupType::FERTILITY),
            $model,
            self::getInstructionFileEnding()
        ]);
    }


    /**
     * @return array
     */
    public static function generateLitterSizeInstructionFile()
    {
        $model = [
            //NOT USED AT THE MOMENT
        ];
        return self::reproductionInstructionFileBase($model, 'Worpgrootte');
    }


    /**
     * @return array
     */
    public static function generateBirthProcessInstructionFile()
    {
        $model = [
            ' GebGemak  ~ JaarBedr '.self::getBreedCodesModel().' CovHetLam CovRecLam !RANDOM WorpID G(ID,IDM)'
        ];
        return self::reproductionInstructionFileBase($model, 'Geboorteverloop');
    }


    /**
     * @return array
     */
    public static function generateFertilityInstructionFile()
    {
        $model = [
            ' TotGeb  ~ Inductie Leeft JaarBedr '.self::getBreedCodesModel().' CovHetLam CovRecLam !RANDOM PermMil G(ID)',
            ' DoodGeb ~ Inductie Leeft JaarBedr '.self::getBreedCodesModel().' CovHetLam CovRecLam !RANDOM PermMil G(ID)',
            ' Vroeg   ~ JaarBedr '.self::getBreedCodesModel().' !RANDOM G(ID)',
            ' # TusLamT',
        ];
        return self::reproductionInstructionFileBase($model, 'Vruchtbaarheid');
    }


}