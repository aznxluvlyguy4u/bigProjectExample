<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ReproductionDataFile
 * @package AppBundle\MixBlup
 */
class ReproductionDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    /**
     * ReproductionDataFile constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        parent::__construct($em, $outputFolderPath);
    }


    /**
     * @param array $middleRecords
     * @return array
     */
    private function reproductionInstructionFileBase(array $middleRecords = [])
    {
        $start = [
            'TITEL   schapen fokwaarde berekening exterieurkenmerken',
            'DATAFILE  '.MixBlupSetting::DATA_FILENAME_PREFIX.MixBlupSetting::EXTERIOR.'.txt',
            ' ID         A !missing '.MixBlupNullFiller::ULN.' #uln van ooi of moeder',  //uln
            ' Leeft      I !missing '.MixBlupNullFiller::COUNT.' #leeftijd ooi in jaren', //age of ewe in years
            ' Sekse      A !missing '.MixBlupNullFiller::GENDER,  //ram/ooi/N_B
            ' JaarBedr   A !missing '.MixBlupNullFiller::GROUP.' #jaar en ubn van geboorte', //year and ubn of birth
        ];

        $lastDataRecords = [
            ' Bedrijf    I !missing '.MixBlupNullFiller::UBN.' #ubn van geboorte',
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            $this->getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middleRecords,
            $lastDataRecords,
            $this->getInstructionFileDefaultEnding()
        ]);
    }


    /**
     * @return array
     */
    private function generateLitterSizeInstructionFile()
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
        return $this->reproductionInstructionFileBase($middleRecords);
    }


    /**
     * @return array
     */
    private function generateBirthProcessInstructionFile()
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
        return $this->reproductionInstructionFileBase($middleRecords);
    }


    /**
     * @return array
     */
    private function generateGaveBirthAsOneYearOldInstructionFile()
    {
        $middleRecords = [
            ' Vroeg      I !missing '.MixBlupNullFiller::PRECOCIOUS.' #Geworpen in eerste levensjaar', //GaveBirthAsOneYearOld 0=FALSE, 1=TRUE
        ];
        return $this->reproductionInstructionFileBase($middleRecords);
    }


    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        return [
            MixBlupSetting::LITTER_SIZE_INSTRUCTION_FILE => $this->generateLitterSizeInstructionFile(),
            MixBlupSetting::BIRTH_PROCESS_INSTRUCTION_FILE => $this->generateBirthProcessInstructionFile(),
            MixBlupSetting::GAVE_BIRTH_AS_ONE_YEAR_OLD_INSTRUCTION_FILE => $this->generateGaveBirthAsOneYearOldInstructionFile(),
        ];
    }

    /**
     * @inheritDoc
     */
    function generateDataFile()
    {
        // TODO: Implement generateDataFile() method.
    }

    /**
     * @inheritDoc
     */
    function generatePedigreeFile()
    {
        // TODO: Implement generatePedigreeFile() method.
    }

    /**
     * @inheritDoc
     */
    function write()
    {
        // TODO: Implement write() method.
    }


}