<?php


namespace AppBundle\Output;


use AppBundle\Cache\BreedValuesResultTableUpdater;
use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Constant\ReportFormat;
use AppBundle\Constant\ReportLabel;
use AppBundle\Criteria\BreedValueTypeCriteria;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedValue;
use AppBundle\Entity\BreedValueType;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\JsonFormat\BreedValueChartDataJsonFormat;
use AppBundle\Service\Report\BreedValuesReportQueryGenerator;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\NumberUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;

class BreedValuesOutput extends OutputServiceBase
{
    const ACCURACY_SUFFIX = '_accuracy';
    const DF_SUFFIX = '_df';

    /** @var BreedValueType[]|ArrayCollection $breedValueTypes */
    private $breedValueTypes;

    /** @var array|float[] */
    private $breedValuesAndAccuracies;

    /** @var array|float[] */
    private $normalizedBreedValues;

    /** @var string */
    private $breedValuesLastGenerationDate;

    /** @var array */
    private $breedValueResultTableColumnNamesSets;

    /** @var array */
    private $resultTableValueVariables;

    /** @var array */
    private $exteriorKeysWithSuffixes;

    /**
     * @param string $nullFiller
     * @return null|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getBreedValuesLastGenerationDate($nullFiller = null): ?string
    {
        if (empty($this->breedValuesLastGenerationDate)) {
            $breedValuesLastGenerationDate = $this->getManager()->getRepository(BreedValue::class)
                ->getBreedValueLastGenerationDate();
            $this->breedValuesLastGenerationDate =
                $breedValuesLastGenerationDate ? $breedValuesLastGenerationDate : $nullFiller;
        }
        return $this->breedValuesLastGenerationDate;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getBreedValueResultTableColumnNamesSets(): array
    {
        if (empty($this->breedValueResultTableColumnNamesSets)) {
            $this->breedValueResultTableColumnNamesSets =
                BreedValuesResultTableUpdater::getResultTableVariables($this->getManager()->getConnection());
        }
        return $this->breedValueResultTableColumnNamesSets;
    }

    /**
     * @return array
     */
    public function initializeResultTableValueVariables(): array
    {
        if (empty($this->resultTableValueVariables)) {
            $this->resultTableValueVariables = [];
            foreach ($this->getExteriorBreedValueTypes() as $exteriorBreedValueType) {
                $resultTableValueVariable = $exteriorBreedValueType->getResultTableValueVariable();
                if (!StringUtil::containsSubstring(self::DF_SUFFIX, $resultTableValueVariable)) {
                    continue;
                }

                $base = strtr($resultTableValueVariable, [self::DF_SUFFIX => '']);
                $this->resultTableValueVariables[] = $base;
            }
        }
        return $this->resultTableValueVariables;
    }

    /**
     * @param array $breedGradesSqlResults
     * @param string $nullFiller
     * @param bool $formatOutput
     * @return array
     */
    public function getForPedigreeCertificate(array $breedGradesSqlResults,
                                              $nullFiller = BreedFormat::EMPTY_BREED_SINGLE_VALUE,
                                              $formatOutput = true)
    {
        $breedValues = [];
        $hasAnyValues = false;
        foreach ($this->initializeResultTableValueVariables() as $resultTableValueVariableBase) {

            // default values
            $breedValues[$resultTableValueVariableBase][ReportLabel::VALUE] =
                $formatOutput ? self::getFormattedBreedValue(null, $nullFiller) : null;
            $breedValues[$resultTableValueVariableBase][ReportLabel::ACCURACY] =
                $formatOutput ? self::getFormattedBreedValueAccuracy(null, $nullFiller) : null;
            $breedValues[$resultTableValueVariableBase][ReportLabel::IS_EMPTY] = true;

            foreach ([ // check the variables in this order
                         self::DF_SUFFIX,
                         '_vg_m',
                         '_vg_v'
                     ] as $suffix) {

                $resultTableValueVariable = $resultTableValueVariableBase.$suffix;
                $resultTableAccuracyVariable = $resultTableValueVariable.self::ACCURACY_SUFFIX;

                $value = ArrayUtil::get($resultTableValueVariable, $breedGradesSqlResults);
                $accuracy = ArrayUtil::get($resultTableAccuracyVariable, $breedGradesSqlResults);

                if (!empty($value) || !empty($accuracy)) {
                    $breedValues[$resultTableValueVariableBase][ReportLabel::VALUE] =
                        $formatOutput ? self::getFormattedBreedValue($value, $nullFiller) : $value;
                    $breedValues[$resultTableValueVariableBase][ReportLabel::ACCURACY] =
                        $formatOutput ? self::getFormattedBreedValueAccuracy($accuracy, $nullFiller) : $accuracy;
                    $breedValues[$resultTableValueVariableBase][ReportLabel::IS_EMPTY] = false;
                    $hasAnyValues = true;
                    break;
                }
            }
        }

        return [
            ReportLabel::VALUES => $breedValues,
            ReportLabel::HAS_ANY_VALUES => $hasAnyValues
        ];
    }


    /**
     * @return array
     */
    public function getExteriorKeysWithSuffixes(): array
    {
        if (empty($this->exteriorKeysWithSuffixes)) {
            $this->exteriorKeysWithSuffixes = [];
            foreach ($this->initializeResultTableValueVariables() as $resultTableValueVariableBase) {
                foreach ([ // check the variables in this order
                             self::DF_SUFFIX,
                             '_vg_m',
                             '_vg_v'
                         ] as $suffix) {

                    $this->exteriorKeysWithSuffixes[] = $resultTableValueVariableBase.$suffix;
                    $this->exteriorKeysWithSuffixes[] = $resultTableValueVariableBase.$suffix.self::ACCURACY_SUFFIX;
                }
            }
        }

        return $this->exteriorKeysWithSuffixes;
    }


    /**
     * @param $value
     * @param $nullFiller
     * @return null|string
     */
    public static function getFormattedBreedValue($value, $nullFiller = BreedFormat::EMPTY_BREED_SINGLE_VALUE): ?string
    {
        return $value ? NumberUtil::getPlusSignIfNumberIsPositive($value) . BreedFormat::formatBreedValueValue($value) : $nullFiller;
    }

    /**
     * @param $accuracy
     * @param $nullFiller
     * @return null|string
     */
    public static function getFormattedBreedValueAccuracy($accuracy, $nullFiller = BreedFormat::EMPTY_BREED_SINGLE_VALUE): ?string
    {
        return $accuracy ? BreedFormat::formatAccuracyForDisplay($accuracy) : $nullFiller;
    }


    /**
     * @param $value
     * @param $nullFiller
     * @return null|string
     */
    public static function getFormattedBreedIndex($value, $nullFiller = BreedFormat::EMPTY_BREED_SINGLE_VALUE): ?string
    {
        return $value ?
            number_format($value, 0, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR)
            : $nullFiller;
    }

    /**
     * @param $accuracy
     * @param $nullFiller
     * @return null|string
     */
    public static function getFormattedBreedIndexAccuracy($accuracy, $nullFiller = BreedFormat::EMPTY_BREED_SINGLE_VALUE): ?string
    {
        return $accuracy ? number_format($accuracy*100, 0, ReportFormat::DECIMAL_CHAR, ReportFormat::THOUSANDS_SEP_CHAR)
            : $nullFiller;
    }


    private function initializeBreedValueTypes(): void
    {
        if (empty($this->breedValueTypes)) {
            $this->breedValueTypes = new ArrayCollection(
                $this->getManager()->getRepository(BreedValueType::class)->findAll()
            );
        }
    }


    public function clearPrivateValues()
    {
        $this->breedValueTypes = null;
        $this->breedValuesAndAccuracies = null;
        $this->normalizedBreedValues = null;

        $this->breedValueResultTableColumnNamesSets = null;
        $this->breedValuesLastGenerationDate = null;

        $this->resultTableValueVariables = null;

        $this->exteriorKeysWithSuffixes = null;
    }


    /**
     * @param Animal $animal
     * @return array
     */
    public function get(Animal $animal)
    {
        if ($animal->getLatestBreedGrades() === null) {
            return [];
        }

        $this->initializeBreedValueTypes();
        $this->breedValuesAndAccuracies = $this->getSerializer()->normalizeResultTableToArray($animal->getLatestBreedGrades());
        $this->normalizedBreedValues = $this->getSerializer()->normalizeResultTableToArray($animal->getLatestNormalizedBreedGrades());

        $breedValueSets = $this->getGeneralBreedValues();
        $breedValueSets = $this->addExteriorBreedValues($breedValueSets);

        $this->clearPrivateValues();

        ksort($breedValueSets);

        return array_values($breedValueSets);
    }


    /**
     * @return array
     */
    private function getGeneralBreedValues()
    {
        $breedValueSets = [];

        foreach ($this->breedValueTypes as $breedValueType)
        {
            $order = $breedValueType->getGraphOrdinal();

            if (!is_int($order) ||
                self::isExteriorAnalysisType($breedValueType)) {
                continue;
            }

            $breedValueSets[$order] = $this->getBreedValueGraphOutput($breedValueType);
        }

        return $breedValueSets;
    }


    /**
     * @return ArrayCollection|BreedValueType[]
     */
    private function getExteriorBreedValueTypes()
    {
        $this->initializeBreedValueTypes();
        if ($this->breedValueTypes === null) {
            return [];
        }

        return $this->breedValueTypes->filter(function(BreedValueType $breedValueType) {
            return $breedValueType->getMixBlupAnalysisType()
                ? $breedValueType->getMixBlupAnalysisType()->getNl() === MixBlupType::EXTERIOR
                : false;
        });
    }


    /**
     * @var array $breedValueSets
     * @return array
     */
    private function addExteriorBreedValues(array $breedValueSets = [])
    {
        foreach ($this->initializeResultTableValueVariables() as $resultTableValueVariableBase) {
            foreach ([ // check the variables in this order
                         self::DF_SUFFIX,
                        '_vg_m',
                         '_vg_v'
                     ] as $suffix) {

                $resultTableValueVariable = $resultTableValueVariableBase.$suffix;
                if (key_exists($resultTableValueVariable, $this->breedValuesAndAccuracies)) {

                    /** @var BreedValueType $breedValueType */
                    $breedValueType = $this->breedValueTypes
                        ->matching(BreedValueTypeCriteria::byResultTableValueVariable($resultTableValueVariable))
                        ->first()
                    ;

                    if ($breedValueType === false) {
                        continue;
                    }

                    $breedValueType->setEn(strtr($breedValueType->getEn(), [
                        '_DF' => '', '_VG_M' => '', '_VG_V' => ''
                    ]));

                    $breedValueSets[$breedValueType->getGraphOrdinal()] =
                        $this->getBreedValueGraphOutput($breedValueType, $this->getExteriorKindFromSuffix($suffix));
                    break;
                }
            }
        }

        return $breedValueSets;
    }


    /**
     * @param $suffix
     * @return string
     */
    private function getExteriorKindFromSuffix($suffix)
    {
        return $suffix ? strtoupper(explode('_', $suffix)[1]) : null;
    }


    /**
     * @param string $exteriorKind
     * @param BreedValueType $breedValueType
     * @return BreedValueChartDataJsonFormat
     */
    private function getBreedValueGraphOutput(BreedValueType $breedValueType, $exteriorKind = null)
    {
        $order = $breedValueType->getGraphOrdinal();

        $value = $this->getValue($breedValueType);
        $normalizedValue = $this->getNormalizedValue($breedValueType);
        $accuracy = $this->getAccuracy($breedValueType);

        $chartLabel = $this->getChartLabel($breedValueType);
        $chartGroup = $this->getChartGroup($breedValueType);
        $chartColor = $this->getChartColor($breedValueType);

        return (new BreedValueChartDataJsonFormat())
            ->setOrdinal($order)
            ->setChartLabel($chartLabel)
            ->setChartGroup($chartGroup)
            ->setChartColor($chartColor)
            ->setValue($value)
            ->setAccuracy($accuracy)
            ->setNormalizedValue($normalizedValue)
            ->setExteriorKind($exteriorKind)
            ->setPrioritizeNormalizedValuesInTable($breedValueType->isPrioritizeNormalizedValuesInReport())
        ;
    }


    /**
     * @param BreedValueType $breedValueType
     * @return float
     */
    private function getValue(BreedValueType $breedValueType)
    {
        $value = ArrayUtil::get($breedValueType->getResultTableValueVariable(), $this->breedValuesAndAccuracies, null);
        return $value !== null ? round($value,BreedValuesReportQueryGenerator::BREED_VALUE_DECIMAL_SPACES) : null;
    }


    /**
     * @param BreedValueType $breedValueType
     * @return mixed|null
     */
    private function getNormalizedValue(BreedValueType $breedValueType)
    {
        if($this->normalizedBreedValues == null)
            return null;
        $value = ArrayUtil::get($breedValueType->getResultTableValueVariable(), $this->normalizedBreedValues, null);
        return $value !== null ? round($value,BreedValuesReportQueryGenerator::NORMALIZED_BREED_VALUE_DECIMAL_SPACES) : null;
    }


    private function getAccuracy(BreedValueType $breedValueType)
    {
        $accuracy = ArrayUtil::get($breedValueType->getResultTableAccuracyVariable(), $this->breedValuesAndAccuracies, null);
        if ($accuracy === null) {
            $keyVersion2 = strtr($breedValueType->getResultTableAccuracyVariable(), ['accuracy' => self::ACCURACY_SUFFIX]);
            $accuracy = ArrayUtil::get($keyVersion2, $this->breedValuesAndAccuracies, null);
        }
        return $accuracy !== null ? round($accuracy * 100) : null;
    }


    /**
     * @param BreedValueType $breedValueType
     * @return string
     */
    private function getChartLabel(BreedValueType $breedValueType)
    {
        switch ($breedValueType->getNl()) {
            case BreedValueTypeConstant::FAT_THICKNESS_3: $en = 'FAT_THICKNESS_AT_20_WEEKS'; break;
            case BreedValueTypeConstant::MUSCLE_THICKNESS: $en = 'MUSCLE_THICKNESS_AT_20_WEEKS'; break;
            default: $en = $breedValueType->getEn(); break;
        }
        return $this->getTranslator()->trans('CHART_LABEL_'.$en);
    }


    /**
     * @param BreedValueType $breedValueType
     * @return string
     */
    private function getChartGroup(BreedValueType $breedValueType)
    {
        return $breedValueType->getGraphGroup() ? $breedValueType->getGraphGroup()->getOrdinal() : null;
    }


    /**
     * @param BreedValueType $breedValueType
     * @return string
     */
    private function getChartColor(BreedValueType $breedValueType)
    {
        return $breedValueType->getGraphGroup() ? $breedValueType->getGraphGroup()->getColor() : null;
    }


    /**
     * @param BreedValueType $breedValueType
     * @return bool
     */
    private static function isExteriorAnalysisType(BreedValueType $breedValueType)
    {
        return $breedValueType->getMixBlupAnalysisType()->getNl() === MixBlupType::EXTERIOR;
    }
}
