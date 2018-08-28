<?php

namespace AppBundle\Entity;

use AppBundle\Setting\BreedGradingSetting;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedValueType
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedValueTypeRepository")
 * @package AppBundle\Entity
 */
class BreedValueType
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var MixBlupAnalysisType
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\MixBlupAnalysisType", inversedBy="breedValueTypes")
     * @ORM\JoinColumn(name="analysis_type_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\MixBlupAnalysisType")
     */
    private $mixBlupAnalysisType;

    /**
     * @var BreedValueGraphGroup
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\BreedValueGraphGroup", inversedBy="breedValueTypes")
     * @ORM\JoinColumn(name="graph_group_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\BreedValueGraphGroup")
     */
    private $graphGroup;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $en;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $nl;

    /**
     * BreedValues for this BreedIndex should have an accuracy that is at least equal to this minAccuracy.
     *
     * @var float
     * @ORM\Column(type="float", options={"default":AppBundle\Setting\BreedGradingSetting::MIN_RELIABILITY_FOR_GENETIC_BASE})
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $minReliability;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $resultTableValueVariable;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $resultTableAccuracyVariable;

    /**
     * Display in reports
     *
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $showResult;

    /**
     * Order in animal details output
     *
     * Due to multiple exterior breedType possibly having the same ordinal, for different animals,
     * this field cannot be set to unique=true;
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $graphOrdinal;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $useNormalDistribution;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $prioritizeNormalizedValuesInReport;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $invertNormalDistribution;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=true)
     * @JMS\Type("float")
     */
    private $standardDeviationStepSize;

    /**
     * BreedValueType constructor.
     * @param string $en
     * @param string $nl
     */
    public function __construct($en, $nl)
    {
        $this->en = $en;
        $this->nl = $nl;
        $this->minReliability = BreedGradingSetting::MIN_RELIABILITY_FOR_GENETIC_BASE;
        $this->resultTableValueVariable = ResultTableBreedGrades::getValueVariableByBreedValueType($en);
        $this->resultTableAccuracyVariable = ResultTableBreedGrades::getAccuracyVariableByBreedValueType($en);
        $this->showResult = true;
        $this->useNormalDistribution = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return BreedValueType
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return MixBlupAnalysisType
     */
    public function getMixBlupAnalysisType()
    {
        return $this->mixBlupAnalysisType;
    }

    /**
     * @param MixBlupAnalysisType $mixBlupAnalysisType
     * @return BreedValueType
     */
    public function setMixBlupAnalysisType($mixBlupAnalysisType)
    {
        $this->mixBlupAnalysisType = $mixBlupAnalysisType;
        return $this;
    }

    /**
     * @return BreedValueGraphGroup
     */
    public function getGraphGroup()
    {
        return $this->graphGroup;
    }

    /**
     * @param BreedValueGraphGroup $graphGroup
     * @return BreedValueType
     */
    public function setGraphGroup($graphGroup)
    {
        $this->graphGroup = $graphGroup;
        return $this;
    }

    /**
     * @return string
     */
    public function getEn()
    {
        return $this->en;
    }

    /**
     * @param string $en
     * @return BreedValueType
     */
    public function setEn($en)
    {
        $this->en = $en;
        return $this;
    }

    /**
     * @return string
     */
    public function getNl()
    {
        return $this->nl;
    }

    /**
     * @param string $nl
     * @return BreedValueType
     */
    public function setNl($nl)
    {
        $this->nl = $nl;
        return $this;
    }

    /**
     * @return float
     */
    public function getMinReliability()
    {
        return $this->minReliability;
    }

    /**
     * @param float $minReliability
     * @return BreedValueType
     */
    public function setMinReliability($minReliability)
    {
        $this->minReliability = $minReliability;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultTableValueVariable()
    {
        return $this->resultTableValueVariable;
    }

    /**
     * @param string $resultTableValueVariable
     * @return BreedValueType
     */
    public function setResultTableValueVariable($resultTableValueVariable)
    {
        $this->resultTableValueVariable = $resultTableValueVariable;
        return $this;
    }

    /**
     * @return string
     */
    public function getResultTableAccuracyVariable()
    {
        return $this->resultTableAccuracyVariable;
    }

    /**
     * @param string $resultTableAccuracyVariable
     * @return BreedValueType
     */
    public function setResultTableAccuracyVariable($resultTableAccuracyVariable)
    {
        $this->resultTableAccuracyVariable = $resultTableAccuracyVariable;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShowResult()
    {
        return $this->showResult;
    }

    /**
     * @param bool $showResult
     * @return BreedValueType
     */
    public function setShowResult($showResult)
    {
        $this->showResult = $showResult;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseNormalDistribution()
    {
        return $this->useNormalDistribution;
    }

    /**
     * @param bool $useNormalDistribution
     * @return BreedValueType
     */
    public function setUseNormalDistribution($useNormalDistribution)
    {
        $this->useNormalDistribution = $useNormalDistribution;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInvertNormalDistribution(): bool
    {
        return $this->invertNormalDistribution;
    }

    /**
     * @param bool $invertNormalDistribution
     * @return BreedValueType
     */
    public function setInvertNormalDistribution(bool $invertNormalDistribution): BreedValueType
    {
        $this->invertNormalDistribution = $invertNormalDistribution;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPrioritizeNormalizedValuesInReport(): bool
    {
        return $this->prioritizeNormalizedValuesInReport;
    }

    /**
     * @param bool $prioritizeNormalizedValuesInReport
     * @return BreedValueType
     */
    public function setPrioritizeNormalizedValuesInReport(bool $prioritizeNormalizedValuesInReport): BreedValueType
    {
        $this->prioritizeNormalizedValuesInReport = $prioritizeNormalizedValuesInReport;
        return $this;
    }

    /**
     * @return float
     */
    public function getStandardDeviationStepSize()
    {
        return $this->standardDeviationStepSize;
    }

    /**
     * @param float $standardDeviationStepSize
     * @return BreedValueType
     */
    public function setStandardDeviationStepSize($standardDeviationStepSize)
    {
        $this->standardDeviationStepSize = $standardDeviationStepSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getGraphOrdinal()
    {
        return $this->graphOrdinal;
    }

    /**
     * @param int $graphOrdinal
     * @return BreedValueType
     */
    public function setGraphOrdinal($graphOrdinal)
    {
        $this->graphOrdinal = $graphOrdinal;
        return $this;
    }



}