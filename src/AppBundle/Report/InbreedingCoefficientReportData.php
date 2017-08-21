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
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\InbreedingCoefficientOffspring;
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
    /** @var Ram */
    private $ram;
    /** @var EweRepository */
    private $eweRepository;
    /** @var RamRepository */
    private $ramRepository;

    /**
     * InbreedingCoefficientReportData constructor.
     * @param ObjectManager $em
     * @param ArrayCollection $content
     * @param Client $client
     */
    public function __construct(ObjectManager $em, ArrayCollection $content, Client $client = null)
    {
        parent::__construct($em, $client, self::FILE_NAME_REPORT_TYPE);
        
        $this->data = [];
        $this->csvData = [];

        /** @var RamRepository $ramRepository */
        $this->ramRepository = $this->em->getRepository(Ram::class);
        /** @var EweRepository $eweRepository */
        $this->eweRepository = $this->em->getRepository(Ewe::class);

        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);
        $ewesArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWES, $content);

        //Initialize default values
        $this->data[ReportLabel::EWES] = array();
        $this->generateRamIdValues($ramArray);        

        if($this->ram == null) {
            $this->data[ReportLabel::IS_RAM_MISSING] = true;
            foreach ($ewesArray as $eweArray) { $this->generateDataWithMissingRam($eweArray); }
            
        } else {
            $this->data[ReportLabel::IS_RAM_MISSING] = false;
            foreach ($ewesArray as $eweArray) { $this->generateDataForEwe($eweArray); }
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
        $this->ram = $this->ramRepository->getRamByArray($ramArray);

        if(!array_key_exists(ReportLabel::PEDIGREE, $this->data[ReportLabel::RAM])) {
            if($this->ram->isPedigreeExists()) {
                $this->data[ReportLabel::RAM][ReportLabel::PEDIGREE] = $this->ram->getPedigreeCountryCode().$this->ram->getPedigreeNumber();
            } else {
                $this->data[ReportLabel::RAM][ReportLabel::PEDIGREE] = self::PEDIGREE_NULL_FILLER;
            }
        }

        if(!array_key_exists(ReportLabel::ULN, $this->data[ReportLabel::RAM])) {
            if($this->ram->isUlnExists()) {
                $this->data[ReportLabel::RAM][ReportLabel::ULN] = $this->ram->getUln();
            } else {
                $this->data[ReportLabel::RAM][ReportLabel::ULN] = self::ULN_NULL_FILLER;
            }
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

        $ewe = $this->eweRepository->getEweByArray($eweArray);
        if($ewe == null) {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = self::PEDIGREE_NULL_FILLER;
        } else {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $ewe->getPedigreeString(self::PEDIGREE_NULL_FILLER);
        }
    }


    /**
     * @param array $eweArray
     */
    private function generateDataForEwe($eweArray)
    {
        $ulnString = AnimalArrayReader::getIdString($eweArray);
        $ewe = $this->eweRepository->getEweByArray($eweArray);

        $this->data[ReportLabel::EWES][$ulnString] = array();

        if($ewe == null) {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = 0;
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = self::PEDIGREE_NULL_FILLER;
        } else {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $ewe->getPedigreeString(self::PEDIGREE_NULL_FILLER);

            $inbreedingCoefficientResult = new InbreedingCoefficientOffspring($this->em, $this->ram->getId(), $ewe->getId());

            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = $inbreedingCoefficientResult->getValue();
        }
    }
    
    
    
}