<?php


namespace AppBundle\Component\MixBlup;


use AppBundle\Enumerator\MixBlupType;
use AppBundle\Setting\MixBlupInstructionFile;
use AppBundle\Setting\MixBlupSetting;
use AppBundle\Util\ArrayUtil;

class WormResistanceInstructionFiles extends MixBlupInstructionFileBase implements MixBlupInstructionFileInterface
{
    /**
     * @inheritDoc
     */
    static function generateInstructionFiles()
    {
        return [
            MixBlupInstructionFile::WORM_RESISTANCE => self::generateWormResistanceInstructionFile(),
            MixBlupInstructionFile::relani(MixBlupInstructionFile::WORM_RESISTANCE) => self::generateWormResistanceRelaniInstructionFile(),
        ];
    }


    /**
     * @return array
     */
    public static function generateWormResistanceInstructionFile()
    {
        return self::generateWormResistanceInstructionFileBase(false);
    }


    /**
     * @return array
     */
    public static function generateWormResistanceRelaniInstructionFile()
    {
        return self::generateWormResistanceInstructionFileBase(true);
    }


    /**
     * @param bool $isRelani
     * @return array
     */
    private static function generateWormResistanceInstructionFileBase($isRelani = false)
    {
        $start = [
            'TITLE   Wormresistentie',
            ' ',
            'DATAFILE  '.MixBlupFileName::getWormResistanceDataFileName().' !MISSING '.self::MISSING_REPLACEMENT,
        ];

        if(MixBlupSetting::INCLUDE_ULNS) {
            $start[] = ' ULN        A';  //uln
        }

        $start[] = ' ID         I';  //primaryKey
        $start[] = ' Sekse      A';  //ram/ooi/N_B
        $start[] = ' JaarBedr   A #jaar en ubn van geboorte'; //year and ubn of birth

        $measurementValues = [
            ' Behandeld  R #0(nee)/1(ja), default = '.WormResistanceDataFile::IS_TREATED_DEFAULT_VALUE,
            ' LnFEC      T #Natuurlijke logaritme uit de eitelling = epg',
            ' SIgA       T #IgA bepaling in Schotland',
            ' NZIgA      T #IgA bepaling in Nieuw Zeeland',
            ' Periode    I #vroege of late monstername binnen seizoen. 1/2, default = '.WormResistanceDataFile::SAMPLE_PERIOD_DEFAULT_VALUE,
            ' ewllwnr    T #eerste worp leeftijd: 1 of 2 (voor alles boven 1), en laatste worp worpnummer',
        ];

        $lastDataRecords = [
            ' Bedrijf    I '.self::getBlockString($isRelani).'#ubn van geboorte', //ubn of birth
        ];

        return ArrayUtil::concatArrayValues([
            $start,
            self::getStandardizedBreedCodePartsAndHetRecOfInstructionFile(),
            $measurementValues,
            $lastDataRecords,
            self::getInstructionFilePedFileToModelHeader(MixBlupType::WORM, $isRelani),
            self::getWormResistanceModel($isRelani),
            self::getInstructionFileEnding($isRelani),
        ]);
    }


    /**
     * @param boolean $isRelani
     * @return array
     */
    public static function getWormResistanceModel($isRelani)
    {
        $jaarBedr = self::jaarBedrijf($isRelani);

        $lnFecTraits = $isRelani ? '' : ' CovTE '.self::getBreedCodesModel().' Sekse Behandeld Periode';
        $siGaTraits = $isRelani ? '' : ' CovTE '.self::getBreedCodesModel().' Sekse';
        $nSiGaTraits = $isRelani ? '' : ' CovTE '.self::getBreedCodesModel().' Sekse Behandeld Periode';

        return [
            'LnFEC' => ' LnFEC    ~ '.$jaarBedr.$lnFecTraits.' !RANDOM G(ID)',
            'SIgA' =>  ' SIgA     ~ '.$jaarBedr.$siGaTraits.' !RANDOM G(ID)',
            'NZIgA' => ' NZIgA    ~ '.$jaarBedr.$nSiGaTraits.' !RANDOM G(ID)',
        ];
    }
}