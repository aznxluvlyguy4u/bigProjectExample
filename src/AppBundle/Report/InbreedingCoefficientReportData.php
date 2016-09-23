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
use AppBundle\Util\InbreedingCoefficientOffspring;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

class InbreedingCoefficientReportData extends ReportBase
{
    const FILE_NAME_REPORT_TYPE = 'inbreeding-coefficient';
    const PEDIGREE_NULL_FILLER = '-';

    /** @var array */
    private $data;

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
    public function __construct(ObjectManager $em, ArrayCollection $content, Client $client)
    {
        parent::__construct($em, $client, self::FILE_NAME_REPORT_TYPE);
        
        $this->data = array();

        /** @var RamRepository $ramRepository */
        $this->ramRepository = $this->em->getRepository(Ram::class);
        /** @var EweRepository $eweRepository */
        $this->eweRepository = $this->em->getRepository(Ewe::class);

        $ramArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::RAM, $content);
        $ewesArray = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::EWES, $content);

        //Initialize default values
        $this->data[ReportLabel::EWES] = array();
        $this->data[ReportLabel::RAM] = AnimalArrayReader::getIdString($ramArray);
        $this->ram = $this->ramRepository->getRamByArray($ramArray);

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
     * @param $eweArray
     */
    private function generateDataWithMissingRam($eweArray)
    {
        $ulnString = AnimalArrayReader::getIdString($eweArray);

        $this->data[ReportLabel::EWES][$ulnString] = array();
        $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = 0;

        $ewe = $this->eweRepository->getEweByArray($eweArray);
        if($ewe == null) {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = '';
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
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = '';
        } else {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $ewe->getPedigreeString(self::PEDIGREE_NULL_FILLER);

            $inbreedingCoefficientResult = new InbreedingCoefficientOffspring($this->em, $this->ram, $ewe);

            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = $inbreedingCoefficientResult->getValue();
        }
    }
    
    
    
}