<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Tests\Compiler\A;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InspectorAuthorization
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InspectorAuthorizationRepository")
 */
class InspectorAuthorization
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Inspector")
     * @ORM\JoinColumn(name="inspector_id", referencedColumnName="id")
     */
    private $inspector;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    private $actionBy;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $measurementType;
    
    /**
     * @var PedigreeRegister
     *
     * @ORM\ManyToOne(targetEntity="PedigreeRegister")
     * @ORM\JoinColumn(name="pedigree_register_id", referencedColumnName="id")
     */
    private $pedigreeRegister;


    /**
     * InspectorAuthorization constructor.
     * @param Inspector $inspector
     * @param Person $actionBy
     * @param string $measurementType
     * @param PedigreeRegister $pedigreeRegister
     */
    public function __construct($inspector, $actionBy, $measurementType, $pedigreeRegister)
    {
        $this->logDate = new \DateTime();
        $this->inspector = $inspector;
        $this->actionBy = $actionBy;
        $this->measurementType = $measurementType;
        $this->pedigreeRegister = $pedigreeRegister;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }

    /**
     * @return Person
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
    }

    /**
     * @return Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }

    /**
     * @param Inspector $inspector
     */
    public function setInspector($inspector)
    {
        $this->inspector = $inspector;
    }

    /**
     * @return string
     */
    public function getMeasurementType()
    {
        return $this->measurementType;
    }

    /**
     * @param string $measurementType
     */
    public function setMeasurementType($measurementType)
    {
        $this->measurementType = $measurementType;
    }

    /**
     * @return PedigreeRegister
     */
    public function getPedigreeRegister()
    {
        return $this->pedigreeRegister;
    }

    /**
     * @param PedigreeRegister $pedigreeRegister
     */
    public function setPedigreeRegister($pedigreeRegister)
    {
        $this->pedigreeRegister = $pedigreeRegister;
    }




}