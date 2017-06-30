<?php

namespace AppBundle\Entity;

use AppBundle\Setting\BreedGradingSetting;
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
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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


}