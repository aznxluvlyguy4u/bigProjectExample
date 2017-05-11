<?php


namespace AppBundle\MixBlup;


use AppBundle\Enumerator\MixBlupNullFiller;
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
            MixBlupInstructionFile::LITTER_SIZE => self::generateLitterSizeInstructionFile(),
            MixBlupInstructionFile::BIRTH_PROGRESS => self::generateBirthProcessInstructionFile(),
            MixBlupInstructionFile::FERTILITY => self::generateGaveBirthAsOneYearOldInstructionFile(),
        ];
    }


    /**
     * @param array $customRecords
     * @param string $titleType
     * @return array
     */
    private static function reproductionInstructionFileBase(array $customRecords = [], $titleType = 'reproductiekenmerken')
    {
        $start = [
            'TITEL   schapen fokwaarde berekening '.$titleType,
            'DATAFILE  '.MixBlupSetting::DATA_FILENAME_PREFIX.MixBlupSetting::EXTERIOR.'.txt',
            ' ID         A !missing '.MixBlupNullFiller::ULN.' #uln van ooi of moeder',  //uln
            ' Leeft      I !missing '.MixBlupNullFiller::COUNT.' #leeftijd ooi in jaren', //age of ewe in years
            ' Sekse      A !missing '.MixBlupNullFiller::GENDER,  //ram/ooi/N_B
            ' JaarBedr   A !missing '.MixBlupNullFiller::GROUP.' #jaar en ubn van geboorte', //year and ubn of birth
        ];

        if($customRecords == []) {
            //By default include all records
            $customRecords = [
                ' HetLam     T !missing '.MixBlupNullFiller::HETEROSIS.' #Heterosis lam of worp', //Heterosis of offspring/litter
                ' RecLam     T !missing '.MixBlupNullFiller::RECOMBINATION.' #Recombinatie lam of worp', //Recombination of offspring/litter
                ' CovTE_M    I !missing '.MixBlupNullFiller::COUNT.' #Rasdeel TE van moeder', //BreedCode part TE of mother //TODO definition still unclear
                ' Inductie   I !missing '.MixBlupNullFiller::PMSG.' #Bronstinduction 0=Ja, 1=Nee', //pmsg value in mate 0=FALSE, 1=TRUE
                ' PermMil    A !missing '.MixBlupNullFiller::ULN.' #Permanent milieu is identiek aan uln van ooi of moeder',
                ' IDM        A !missing '.MixBlupNullFiller::ULN.' #Het unieke diernummer van de moeder', //TODO definition still unclear
                ' TotGeb     I !missing '.MixBlupNullFiller::COUNT.' #Totaal geboren lammeren in de worp', //bornAliveCount in litter
                ' DoodGeb    I !missing '.MixBlupNullFiller::COUNT.' #Doodgeboren lammeren in de worp', //stillbornCount in litter
                ' GewGeb     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #Geboortegewicht', //birthWeight
                ' GebGemak   I !missing '.MixBlupNullFiller::BIRTH_PROGRESS.' #Geboortegemak', //birthProgress from 0 to 4
                ' DrTijd     I !missing '.MixBlupNullFiller::AGE.' #Draagtijd', //gestationPeriod in litter
                ' TusLamT    I !missing '.MixBlupNullFiller::AGE.' #Tussenlamtijd', //birthInterval in litter
                ' Vroeg      I !missing '.MixBlupNullFiller::PRECOCIOUS.' #Geworpen in eerste levensjaar', //GaveBirthAsOneYearOld 0=FALSE, 1=TRUE
            ];
        }

        $lastDataRecords = [
            ' Bedrijf    I !missing '.MixBlupNullFiller::UBN.' #ubn van geboorte',
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $customRecords,
            $lastDataRecords,
            self::getInstructionFileDefaultEnding()
        ]);
    }


    /**
     * @return array
     */
    public static function generateLitterSizeInstructionFile()
    {
        $middleRecords = [
            ' HetLam     T !missing '.MixBlupNullFiller::HETEROSIS.' #Heterosis lam of worp', //Heterosis of offspring/litter
            ' RecLam     T !missing '.MixBlupNullFiller::RECOMBINATION.' #Recombinatie lam of worp', //Recombination of offspring/litter
            ' Inductie   I !missing '.MixBlupNullFiller::PMSG.' #Bronstinduction 0=Ja, 1=Nee', //pmsg value in mate 0=FALSE, 1=TRUE
            ' PermMil    A !missing '.MixBlupNullFiller::ULN.' #Permanent milieu is identiek aan uln van ooi of moeder',
            ' TotGeb     I !missing '.MixBlupNullFiller::COUNT.' #Totaal geboren lammeren in de worp', //bornAliveCount in litter
            ' DoodGeb    I !missing '.MixBlupNullFiller::COUNT.' #Doodgeboren lammeren in de worp', //stillbornCount in litter
            ' TusLamT    I !missing '.MixBlupNullFiller::AGE.' #Tussenlamtijd', //birthInterval in litter
        ];
        return self::reproductionInstructionFileBase($middleRecords, 'worpgrootte');
    }


    /**
     * @return array
     */
    public static function generateBirthProcessInstructionFile()
    {
        $middleRecords = [
            ' HetLam     T !missing '.MixBlupNullFiller::HETEROSIS.' #Heterosis lam of worp', //Heterosis of offspring/litter
            ' RecLam     T !missing '.MixBlupNullFiller::RECOMBINATION.' #Recombinatie lam of worp', //Recombination of offspring/litter
            ' CovTE_M    I !missing '.MixBlupNullFiller::COUNT.' #Rasdeel TE van moeder', //BreedCode part TE of mother //TODO definition still unclear
            ' IDM        A !missing '.MixBlupNullFiller::ULN.' #Het unieke diernummer van de moeder', //TODO definition still unclear
            ' GewGeb     T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #Geboortegewicht', //birthWeight
            ' GebGemak   I !missing '.MixBlupNullFiller::BIRTH_PROGRESS.' #Geboortegemak', //birthProgress from 0 to 4
            ' DrTijd     I !missing '.MixBlupNullFiller::AGE.' #Draagtijd', //gestationPeriod in litter
        ];
        return self::reproductionInstructionFileBase($middleRecords, 'geboorteverloop');
    }


    /**
     * @return array
     */
    public static function generateGaveBirthAsOneYearOldInstructionFile()
    {
        $middleRecords = [
            ' Vroeg      I !missing '.MixBlupNullFiller::PRECOCIOUS.' #Geworpen in eerste levensjaar', //GaveBirthAsOneYearOld 0=FALSE, 1=TRUE
        ];
        return self::reproductionInstructionFileBase($middleRecords, 'vroegrijpheid');
    }


}