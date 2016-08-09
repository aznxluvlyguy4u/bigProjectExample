<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Animal;
use AppBundle\Enumerator\GenderType;
use AppBundle\Util\CommandUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Collection;

class Mixblup
{
    const PARENT_NULL_FILLER = 0;
    const BREED_CODE_NULL_FILLER = 0;
    const GENDER_NULL_FILLER = 0;
    const DATE_OF_BIRTH_NULL_FILLER = 0;
    const RAM = 'ram';
    const EWE = 'ooi';
    const NEUTER = 0;
    const COLUMN_PADDING_SIZE = 2;

    /** @var EntityManager */
    private $em;

    /** @var array */
    private $animals;

    /** @var string */
    private $instructionsFilePath;

    /** @var string */
    private $dataFilePath;

    /** @var string */
    private $pedigreeFilePath;

    /** @var string */
    private $instructionsFileName;

    /** @var string */
    private $dataFileName;

    /** @var string */
    private $pedigreeFileName;


    /**
     * Mixblup constructor.
     * @param EntityManager $em
     * @param string $outputFolderPath
     * @param string $instructionsFileName
     * @param string $dataFileName
     * @param string $pedigreeFileName
     * @param array $animals
     */
    public function __construct(EntityManager $em, $outputFolderPath, $instructionsFileName, $dataFileName, $pedigreeFileName, $animals = null)
    {
        $this->em = $em;

        if($animals != null) {
            $this->animals = $animals;
        } else {
            $this->animals = $this->em->getRepository(Animal::class)->findAll();
        }

        $this->dataFileName = $dataFileName;
        $this->pedigreeFileName = $pedigreeFileName;
        $this->instructionsFileName = $instructionsFileName;

        if(substr($outputFolderPath, -1) != '/') {
            $this->dataFilePath = $outputFolderPath.'/'.$dataFileName;
            $this->pedigreeFilePath = $outputFolderPath.'/'.$pedigreeFileName;
            $this->instructionsFilePath = $outputFolderPath.'/'.$instructionsFileName;
        } else {
            $this->dataFilePath = $outputFolderPath.$dataFileName;
            $this->pedigreeFilePath = $outputFolderPath.$pedigreeFileName;
            $this->instructionsFilePath = $outputFolderPath.$instructionsFileName;
        }

    }

    /**
     * @return array
     */
    public function generateInstructionArray()
    {
        return [
            'TITEL   schapen fokwaarde berekening groei, spierdikte en vetbedekking',
            ' DATAFILE  '.$this->dataFileName,
            ' animal     A',  //uln
            ' gender     A',  //ram/ooi/0
            ' rascode    A',  //breedCode
            ' n-ling     I',  //Litter->size()
            ' worpnummer A',
            ' moeder     A',  //uln of mother
            ' meetdatum  A', //measurementDate
            ' vet1       T',
            ' vet2       T',
            ' vet3       T',
            ' spierdik   T',
            ' staartlg   T', //staartlengte
            ' gebgewicht T',
            ' toetsgewicht T', //staartlengte
            ' KOP T',
            ' ONT T',
            ' BES T',
            ' EVE T',
            ' TYP T',
            ' BEE T',
            ' VAC T',
            ' ALG T',
            ' SHT T',
            ' LGT T',
            ' BDP T',
            ' STT T',
            ' ',
            'PEDFILE   '.$this->pedigreeFileName,
            ' animal    A',
            ' sire      A',
            ' dam       A',
            ' gender    A',
            ' gebjaar   A',
            ' rascode   A',
            ' ',
            'PARFILE  *insert par file reference here*',
            ' ',
            'MODEL    *insert model settings here*',
            ' ',
            'SOLVING  *insert solve settings here*'

        ];
    }


    /**
     * @return string
     */
    public function generateInstructionFile()
    {
        foreach($this->generateInstructionArray() as $row) {
            file_put_contents($this->instructionsFilePath, $row."\n", FILE_APPEND);
        }
        return $this->instructionsFilePath;
    }
    

    /**
     * @return array
     */
    public function generatePedigreeArray()
    {
        $result = array();
        
        foreach ($this->animals as $animal) {
            $result[] = $this->writePedigreeRecord($animal);
        }
        
        return $result;
    }

    
    /**
     * @return string
     */
    public function generatePedigreeFile()
    {
        foreach($this->generatePedigreeArray() as $row) {
            file_put_contents($this->pedigreeFilePath, $row."\n", FILE_APPEND);
        }
        return $this->pedigreeFilePath;
    }


    /**
     * @return array
     */
    public function generateDataArray()
    {
        $result = array();

        foreach ($this->animals as $animal) {
            $result[] = $this->writeDataRecord($animal);
        }

        return $result;
    }


    /**
     * @return string
     */
    public function generateDataFile()
    {
        foreach($this->generateDataArray() as $row) {
            file_put_contents($this->dataFilePath, $row."\n", FILE_APPEND);
        }
        return $this->dataFilePath;
    }


    /**
     * @param Animal $animal
     * @return string
     */
    private function writePedigreeRecord(Animal $animal)
    {
        $animalUln = self::formatUln($animal);
        $parents = CommandUtil::getParentUlnsFromParentsArray($animal->getParents(), self::PARENT_NULL_FILLER);
        $motherUln = $parents->get(Constant::MOTHER_NAMESPACE);
        $fatherUln = $parents->get(Constant::FATHER_NAMESPACE);

        $breedCode = Utils::fillNullOrEmptyString($animal->getBreedCode(), self::BREED_CODE_NULL_FILLER);
        $gender = self::formatGender($animal->getGender());
        $dateOfBirthString = self::formatDate($animal->getDateOfBirth());

        $record =
        Utils::addPaddingToStringForColumnFormatSides($animalUln, 15).
        Utils::addPaddingToStringForColumnFormatCenter($fatherUln, 19, self::COLUMN_PADDING_SIZE).
        Utils::addPaddingToStringForColumnFormatCenter($motherUln, 19, self::COLUMN_PADDING_SIZE).
        Utils::addPaddingToStringForColumnFormatCenter($gender, 7, self::COLUMN_PADDING_SIZE).
        Utils::addPaddingToStringForColumnFormatCenter($dateOfBirthString, 10, self::COLUMN_PADDING_SIZE).
        Utils::addPaddingToStringForColumnFormatSides($breedCode, 12);

        return $record;
    }

    /**
     * @param Animal $animal
     * @return string
     */
    private function writeDataRecord(Animal $animal)
    {
        //TODO return the real desired values
        $animalUln = self::formatUln($animal);
        $parents = CommandUtil::getParentUlnsFromParentsArray($animal->getParents(), self::PARENT_NULL_FILLER);
        $motherUln = $parents->get(Constant::MOTHER_NAMESPACE);
        $fatherUln = $parents->get(Constant::FATHER_NAMESPACE);

        $breedCode = Utils::fillNullOrEmptyString($animal->getBreedCode(), self::BREED_CODE_NULL_FILLER);
        $gender = self::formatGender($animal->getGender());
        $dateOfBirthString = self::formatDate($animal->getDateOfBirth());

        $record =
            Utils::addPaddingToStringForColumnFormatSides($animalUln, 15).
            Utils::addPaddingToStringForColumnFormatCenter($fatherUln, 19, self::COLUMN_PADDING_SIZE).
            Utils::addPaddingToStringForColumnFormatCenter($motherUln, 19, self::COLUMN_PADDING_SIZE).
            Utils::addPaddingToStringForColumnFormatCenter($gender, 7, self::COLUMN_PADDING_SIZE).
            Utils::addPaddingToStringForColumnFormatCenter($dateOfBirthString, 10, self::COLUMN_PADDING_SIZE).
            Utils::addPaddingToStringForColumnFormatSides($breedCode, 12);

        return $record;
    }

    /**
     * @param \DateTime|null $dateTime
     * @return string|boolean   string when formatting was successful, false if it failed
     */
    public static function formatDate($dateTime)
    {
        if($dateTime == null) {
            return self::DATE_OF_BIRTH_NULL_FILLER;
        } else {
            return date_format($dateTime, "Ymd");
        }
    }

    /**
     * @param $gender
     * @return string|int
     */
    public static function formatGender($gender)
    {
        if($gender == GenderType::M || $gender == GenderType::MALE) {
            $gender = self::RAM;
        } else if($gender == GenderType::V || $gender == GenderType::FEMALE) {
            $gender = self::EWE;
        } else {
            $gender = self::GENDER_NULL_FILLER;
        }
        
        return $gender;
    }


    /**
     * @param Animal $animal
     * @param mixed $nullFiller
     * @return string
     */
    public static function formatUln($animal, $nullFiller = 0)
    {
        if($animal->getUlnCountryCode() != null && $animal->getUlnNumber() != null)
        {
            $result = $animal->getUlnCountryCode().$animal->getUlnNumber();
        } else {
            $result = $nullFiller;
        }

        return $result;
    }
}