<?php

namespace AppBundle\Report;


use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\EweRepository;
use AppBundle\Entity\Ram;
use AppBundle\Entity\RamRepository;
use AppBundle\Enumerator\PedigreeMasterKey;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\InbreedingCoefficientOffspring;
use AppBundle\Util\PedigreeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientReportData extends ReportBase
{
    const FILE_NAME_REPORT_TYPE = 'inbreeding-coefficient';
    const PEDIGREE_NULL_FILLER = '-';
    const ULN_NULL_FILLER = '-';

    /** @var array */
    private $data;
    /** @var array */
    private $csvData;
    /** @var array */
    private $ramData;
    /** @var array */
    private $ewesData;
    /** @var array */
    private $parentAscendants;

    /**
     * InbreedingCoefficientReportData constructor.
     * @param ObjectManager $em
     * @param array $ramData
     * @param array $ewesData
     * @param int $generationOfAscendants
     * @param Client $client
     */
    public function __construct(ObjectManager $em, $ramData, $ewesData, $generationOfAscendants, Client $client = null)
    {
        parent::__construct($em, $client, self::FILE_NAME_REPORT_TYPE);
        
        $this->data = [];
        $this->csvData = [];

        $this->ramData = $ramData;
        $this->ewesData = $ewesData;

        //Initialize default values
        $this->data[ReportLabel::EWES] = array();
        $this->generateRamIdValues($this->ramData);


        $parentIds = [];
        $parentIds[$this->ramData['id']] = $this->ramData['id'];
        foreach ($this->ewesData as $eweData) {
            $eweId = $eweData['id'];
            $parentIds[$eweId] = $eweId;
        }

        $this->parentAscendants = PedigreeUtil::findNestedParentsBySingleSqlQuery($this->conn, $parentIds, $generationOfAscendants,PedigreeMasterKey::ULN);


        if($this->ramData == null) {
            $this->data[ReportLabel::IS_RAM_MISSING] = true;
            foreach ($this->ewesData as $eweArray) { $this->generateDataWithMissingRam($eweArray); }
            
        } else {
            $this->data[ReportLabel::IS_RAM_MISSING] = false;
            foreach ($this->ewesData as $eweArray) { $this->generateDataForEwe($eweArray); }
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }


    /**
     * @return array
     */
    public function getCsvData()
    {
        if (count($this->csvData) !== 0) { return $this->csvData; }

        $nullReplacement = null;

        $ramData = ArrayUtil::get(ReportLabel::RAM, $this->data);
        $ramUln = $nullReplacement;
        $ramStn = $nullReplacement;
        if ($ramData) {
            $ramUln = ArrayUtil::get(ReportLabel::ULN, $ramData, $ramUln);
            $ramStn = ArrayUtil::get(ReportLabel::PEDIGREE, $ramData, $ramStn);
        }

        $csvOutput = [];
        foreach (ArrayUtil::get(ReportLabel::EWES, $this->data, []) as $eweUln => $eweData) {
            $eweStn = ArrayUtil::get(ReportLabel::PEDIGREE, $eweData, $nullReplacement);
            $inbreedingCoefficient = ArrayUtil::get(ReportLabel::INBREEDING_COEFFICIENT, $eweData, $nullReplacement);

            $csvOutput[] = [
                $this->getCsvKey(ReportLabel::RAM.ReportLabel::ULN) => $ramUln,
                $this->getCsvKey(ReportLabel::RAM.ReportLabel::STN) => $ramStn,
                $this->getCsvKey(ReportLabel::EWE.ReportLabel::ULN) => $eweUln,
                $this->getCsvKey(ReportLabel::EWE.ReportLabel::STN) => $eweStn,
                $this->getCsvKey(ReportLabel::INBREEDING_COEFFICIENT) => $inbreedingCoefficient,
            ];
        }
        return $csvOutput;
    }


    /**
     * @param string $reportLabel
     * @return string
     */
    private function getCsvKey($reportLabel)
    {
        $dutchLabels = [
            ReportLabel::RAM.ReportLabel::ULN => ReportLabel::RAM.'_'.ReportLabel::ULN,
            ReportLabel::RAM.ReportLabel::STN => ReportLabel::RAM.'_'.ReportLabel::STN,
            ReportLabel::EWE.ReportLabel::ULN => ReportLabel::EWE.'_'.ReportLabel::ULN,
            ReportLabel::EWE.ReportLabel::STN => ReportLabel::EWE.'_'.ReportLabel::STN,
            ReportLabel::INBREEDING_COEFFICIENT => 'inteeltcoëfficiënt',
        ];

        return $dutchLabels[$reportLabel];
    }




    /**
     * @param array $ramArray
     */
    private function generateRamIdValues($ramArray)
    {
        $this->data[ReportLabel::RAM] = AnimalArrayReader::getUlnAndPedigreeInArray($ramArray);

        if(!array_key_exists(ReportLabel::PEDIGREE, $this->data[ReportLabel::RAM])) {
            $this->data[ReportLabel::RAM][ReportLabel::PEDIGREE] = $this->getPedigreeString($ramArray);
        }

        if(!array_key_exists(ReportLabel::ULN, $this->data[ReportLabel::RAM])) {
            $this->data[ReportLabel::RAM][ReportLabel::ULN] = AnimalArrayReader::getUlnFromArray($ramArray, self::ULN_NULL_FILLER);
        }
    }
    

    /**
     * @param $eweArray
     */
    private function generateDataWithMissingRam($eweArray)
    {
        $ulnString = AnimalArrayReader::getIdString($eweArray);

        $this->data[ReportLabel::EWES][$ulnString] = array();
        $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = 0;
        $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $this->getPedigreeString($eweArray);
    }


    /**
     * @param array $eweArray
     */
    private function generateDataForEwe($eweArray)
    {
        $ulnString = AnimalArrayReader::getIdString($eweArray);

        $this->data[ReportLabel::EWES][$ulnString] = array();

        if($eweArray == null) {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = 0;
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = self::PEDIGREE_NULL_FILLER;
        } else {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $this->getPedigreeString($eweArray);

            $inbreedingCoefficientResult = new InbreedingCoefficientOffspring($this->em,
                $this->ramData, $eweArray, [], [], [], $this->parentAscendants);

            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = $inbreedingCoefficientResult->getValue();
        }
    }


    /**
     * @param array $animalArray
     * @return string
     */
    private function getPedigreeString($animalArray)
    {
        if (is_array($animalArray)) {
            $ulnCountryCode = $animalArray['pedigree_country_code'];
            $ulnNumber = $animalArray['pedigree_number'];
            if ($ulnCountryCode !== null && $ulnNumber !== null) {
                return $ulnCountryCode . $ulnNumber;
            }
        }
        return self::PEDIGREE_NULL_FILLER;
    }
    
    
}