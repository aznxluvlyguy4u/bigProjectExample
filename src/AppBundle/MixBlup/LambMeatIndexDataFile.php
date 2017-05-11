<?php


namespace AppBundle\MixBlup;

use AppBundle\Enumerator\MixBlupNullFiller;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LambMeatIndexDataFile
 * @package AppBundle\MixBlup
 */
class LambMeatIndexDataFile extends MixBlupDataFileBase implements MixBlupDataFileInterface
{
    /**
     * LambMeatIndexDataFile constructor.
     * @param ObjectManager $em
     * @param string $outputFolderPath
     */
    public function __construct(ObjectManager $em, $outputFolderPath)
    {
        parent::__construct($em, $outputFolderPath);
    }


    /**
     * @param array $customRecords
     * @param string $fileType
     * @return array
     */
    private function generateTestAttributeInstructionFile(array $customRecords = [], $fileType = 'vleeslamkenmerken')
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
            $this->getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $middle,
            $customRecords,
            $lastDataRecords,
            $this->getInstructionFileDefaultEnding()
        ]);
    }


    /**
     * @return array
     */
    private function generateLambMeatInstructionFile()
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
        return $this->generateTestAttributeInstructionFile($customRecords, 'vleeslamkenmerken');
    }


    /**
     * @return array
     */
    private function generateTailLengthInstructionFile()
    {
        $customRecords = [
            ' StaartLen  T !missing '.MixBlupNullFiller::MEASUREMENT_VALUE.' #staartlengte', //tailLength
        ];
        return $this->generateTestAttributeInstructionFile($customRecords, 'staartlengte');
    }
    
    
    /**
     * @inheritDoc
     */
    function generateInstructionFiles()
    {
        return [
            MixBlupInstructionFile::LAMB_MEAT => $this->generateLambMeatInstructionFile(),
            MixBlupInstructionFile::TAIL_LENGTH => $this->generateTailLengthInstructionFile(),
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




}