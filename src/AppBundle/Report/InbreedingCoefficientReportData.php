<?php

namespace AppBundle\Report;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\ReportLabel;
use AppBundle\Entity\Client;
use AppBundle\Entity\InbreedingCoefficient;
use AppBundle\Service\Report\InbreedingCoefficientReportService;
use AppBundle\Util\AnimalArrayReader;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ParentIdsPairUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Translation\TranslatorInterface;

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
    private $ramsData;
    /** @var array */
    private $ewesData;
    /** @var TranslatorInterface */
    protected $translator;
    /** @var array|float[] */
    private $inbreedingCoefficientValuesByPairId;

    /**
     * InbreedingCoefficientReportData constructor.
     * @param ObjectManager $em
     * @param $translator
     * @param array $ramsData
     * @param array $ewesData
     * @param array|InbreedingCoefficient[] $inbreedingCoefficients
     * @param Client $client
     */
    public function __construct(ObjectManager $em, $translator, $ramsData, $ewesData,
                                $inbreedingCoefficients,
                                Client $client = null)
    {
        parent::__construct($em, $client, self::FILE_NAME_REPORT_TYPE);
        $this->translator = $translator;

        $this->data = [];
        $this->csvData = [];

        $this->data[ReportLabel::COLOR_CELLS] = true;

        $this->data['date'] = TimeUtil::getTimeStampToday('d-m-Y');

        $this->ramsData = $ramsData;
        $this->ewesData = $ewesData;

        $this->data['hasRam2'] = 2 <= count($ramsData);
        $this->data['hasRam3'] = 3 <= count($ramsData);
        $this->data['hasRam4'] = 4 <= count($ramsData);
        $this->data['hasRam5'] = 5 <= count($ramsData);

        //Initialize default values
        $this->data[ReportLabel::EWES] = [];
        $this->generateRamsIdValues($this->ramsData);

        $parentIds = [];
        foreach ($this->ramsData as $ramData) {
            $ramId = $ramData['id'];
            $parentIds[$ramId] = $ramId;
        }
        foreach ($this->ewesData as $eweData) {
            $eweId = $eweData['id'];
            $parentIds[$eweId] = $eweId;
        }

        $this->inbreedingCoefficientValuesByPairId = [];
        foreach ($inbreedingCoefficients as $inbreedingCoefficient) {
            $this->inbreedingCoefficientValuesByPairId[$inbreedingCoefficient->getPairId()] = $inbreedingCoefficient->getValue();
        }

        if($this->ramsData == null) {
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
        $csvOutput = [];

        // CSV HEADER
        $ramKey = mb_strtolower($this->translator->trans(strtoupper(ReportLabel::RAM)));
        $eweKey = mb_strtolower($this->translator->trans(strtoupper(ReportLabel::EWE)));
        $ulnKey = mb_strtolower($this->translator->trans(strtoupper(ReportLabel::ULN)));
        $stnKey = mb_strtolower($this->translator->trans(strtoupper(ReportLabel::STN)));
        $inbreedingCoefficientKey = mb_strtolower(
            strtr($this->translator->trans(strtoupper(ReportLabel::INBREEDING_COEFFICIENT)),
                ['Ë' => 'E', ' ' => '_'])
        );
        $ramOrdinalKey = $ramKey.'#';

        foreach (ArrayUtil::get(ReportLabel::RAMS, $this->data) as $ordinal => $ramData) {

            $ramUln = ArrayUtil::get(ReportLabel::ULN, $ramData, $nullReplacement);
            $ramStn = ArrayUtil::get(ReportLabel::PEDIGREE, $ramData, $nullReplacement);

            foreach (ArrayUtil::get(ReportLabel::EWES, $this->data, []) as $eweUln => $eweData) {
                $eweStn = ArrayUtil::get(ReportLabel::PEDIGREE, $eweData, $nullReplacement);
                $inbreedingCoefficient = InbreedingCoefficientReportService::parseInbreedingCoefficientValueForDisplay(
                    $this->getInbreedingCoefficient($ramData['id'], $eweData['id'])
                );

                $csvOutput[] = [
                    $ramOrdinalKey => $ordinal,
                    $ramKey.'_'.$ulnKey => $ramUln,
                    $ramKey.'_'.$stnKey => $ramStn,
                    $eweKey.'_'.$ulnKey => $eweUln,
                    $eweKey.'_'.$stnKey => $eweStn,
                    $inbreedingCoefficientKey => $inbreedingCoefficient,
                ];
            }
        }

        return $csvOutput;
    }


    private function getInbreedingCoefficient(int $ramId, int $eweId): ?float
    {
        $nullReplacement = 0.0;
        return ArrayUtil::get(
            InbreedingCoefficient::generatePairId($ramId, $eweId),
            $this->inbreedingCoefficientValuesByPairId,
            $nullReplacement
        );
    }


    /**
     * @param array $ramsArray
     */
    private function generateRamsIdValues($ramsArray)
    {
        foreach ($ramsArray as $ramArray) {
            $this->generateRamIdValues($ramArray);
        }
    }


    /**
     * @param array $ramArray
     */
    private function generateRamIdValues($ramArray)
    {
        $ordinal = $ramArray[ReportLabel::ORDINAL];
        $this->data[ReportLabel::RAMS][$ordinal] = AnimalArrayReader::addUlnAndPedigreeToArray($ramArray);

        if(!array_key_exists(ReportLabel::PEDIGREE, $this->data[ReportLabel::RAMS][$ordinal])) {
            $this->data[ReportLabel::RAMS][$ordinal][ReportLabel::PEDIGREE] = $this->getPedigreeString($ramArray);
        }

        if(!array_key_exists(ReportLabel::ULN, $this->data[ReportLabel::RAMS][$ordinal])) {
            $this->data[ReportLabel::RAMS][$ordinal][ReportLabel::ULN] = AnimalArrayReader::getUlnFromArray($ramArray, self::ULN_NULL_FILLER);
        }
    }


    /**
     * @param $eweArray
     */
    private function generateDataWithMissingRam($eweArray)
    {
        $ulnString = AnimalArrayReader::getIdString($eweArray);

        $this->data[ReportLabel::EWES][$ulnString] = [];
        $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = 0;
        $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $this->getPedigreeString($eweArray);
    }


    /**
     * @param array $eweArray
     */
    private function generateDataForEwe($eweArray)
    {
        $ulnString = AnimalArrayReader::getIdString($eweArray);

        $this->data[ReportLabel::EWES][$ulnString] = $eweArray;

        if($eweArray == null) {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT] = 0;
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = self::PEDIGREE_NULL_FILLER;
        } else {
            $this->data[ReportLabel::EWES][$ulnString][ReportLabel::PEDIGREE] = $this->getPedigreeString($eweArray);

            $eweId = $eweArray[JsonInputConstant::ID];

            foreach ($this->ramsData as $ramData) {
                $ordinal = $ramData[ReportLabel::ORDINAL];
                $ramId = $ramData[JsonInputConstant::ID];

                $inbreedingCoefficient = $this->getInbreedingCoefficient($ramId, $eweId);
                $color = InbreedingCoefficientReportService::inbreedingCoefficientColor($inbreedingCoefficient);

                $this->data[ReportLabel::EWES][$ulnString][ReportLabel::INBREEDING_COEFFICIENT][$ordinal] =
                    $inbreedingCoefficient;
                $this->data[ReportLabel::EWES][$ulnString][ReportLabel::COLOR][$ordinal] =
                    $color;
            }
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
