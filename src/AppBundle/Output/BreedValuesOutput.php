<?php


namespace AppBundle\Output;


use AppBundle\Component\BreedGrading\BreedFormat;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Criteria\BreedValueTypeCriteria;
use AppBundle\Entity\Animal;
use AppBundle\Entity\BreedValueType;
use AppBundle\Entity\MixBlupAnalysisType;
use AppBundle\Enumerator\MixBlupType;
use AppBundle\JsonFormat\BreedValueChartDataJsonFormat;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;

class BreedValuesOutput extends OutputServiceBase
{
    /** @var BreedValueType[]|ArrayCollection $breedValueTypes */
    private $breedValueTypes;

    /** @var array|float[] */
    private $breedValuesAndAccuracies;

    /**
     * @param Animal $animal
     * @return array
     */
    public function get(Animal $animal)
    {
        if ($animal->getLatestBreedGrades() === null) {
            return [];
        }

        /** @var BreedValueType[] $breedValueTypes */
        $this->breedValueTypes = new ArrayCollection($this->getManager()->getRepository(BreedValueType::class)->findAll());
        $this->breedValuesAndAccuracies = $this->getSerializer()->normalizeToArray($animal->getLatestBreedGrades());

        $breedValueSets = $this->getGeneralBreedValues();
        $breedValueSets = $this->addExteriorBreedValues($breedValueSets);

        $this->breedValueTypes = null;
        $this->breedValuesAndAccuracies = null;

        ksort($breedValueSets);

        return $breedValueSets;
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
        $exteriorBreedValueTypes = $this->getExteriorBreedValueTypes();

        $dfSuffix = '_df';

        $resultTableValueVariables = [];
        foreach ($exteriorBreedValueTypes as $exteriorBreedValueType) {
            $resultTableValueVariable = $exteriorBreedValueType->getResultTableValueVariable();
            if (!StringUtil::containsSubstring($dfSuffix, $resultTableValueVariable)) {
                continue;
            }

            $base = strtr($resultTableValueVariable, [$dfSuffix => '']);
            $resultTableValueVariables[] = $base;
        }

        foreach ($resultTableValueVariables as $resultTableValueVariableBase) {
            foreach ([ // check the variables in this order
                        $dfSuffix,
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
        ;
    }


    /**
     * @param BreedValueType $breedValueType
     * @return float
     */
    private function getValue(BreedValueType $breedValueType)
    {
        $value = ArrayUtil::get($breedValueType->getResultTableValueVariable(), $this->breedValuesAndAccuracies, null);
        return $value !== null ? round($value,BreedFormat::DEFAULT_DECIMAL_ACCURACY) : null;
    }


    /**
     * TODO no normalized breed values are currently available, except for WormResistance
     *
     * @param BreedValueType $breedValueType
     * @return mixed|null
     */
    private function getNormalizedValue(BreedValueType $breedValueType)
    {
        return $this->getValue($breedValueType); // TODO
    }


    private function getAccuracy(BreedValueType $breedValueType)
    {
        $accuracy = ArrayUtil::get($breedValueType->getResultTableAccuracyVariable(), $this->breedValuesAndAccuracies, null);
        if ($accuracy === null) {
            $keyVersion2 = strtr($breedValueType->getResultTableAccuracyVariable(), ['accuracy' => '_accuracy']);
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