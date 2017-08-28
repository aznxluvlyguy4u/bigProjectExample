<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedIndexType
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedIndexTypeRepository")
 * @package AppBundle\Entity
 */
class BreedIndexType
{
    use EntityClassInfo;

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
        $this->resultTableValueVariable = ResultTableBreedGrades::getValueVariableByBreedIndexType($en);
        $this->resultTableAccuracyVariable = ResultTableBreedGrades::getAccuracyVariableByBreedIndexType($en);
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
     * @return BreedIndexType
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
     * @return BreedIndexType
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
     * @return BreedIndexType
     */
    public function setNl($nl)
    {
        $this->nl = $nl;
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
     * @return BreedIndexType
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
     * @return BreedIndexType
     */
    public function setResultTableAccuracyVariable($resultTableAccuracyVariable)
    {
        $this->resultTableAccuracyVariable = $resultTableAccuracyVariable;
        return $this;
    }


}