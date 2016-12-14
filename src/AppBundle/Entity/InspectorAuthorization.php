<?php

namespace AppBundle\Entity;

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
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var Person
     *
     * @ORM\Column(type="string")
     * @ORM\JoinColumn(name="measurement_type", referencedColumnName="id")
     */
    private $measurementType;
    
    /**
     * @var PedigreeRegister
     *
     * @ORM\ManyToOne(targetEntity="PedigreeRegister")
     * @ORM\JoinColumn(name="pedigree_register_id", referencedColumnName="id")
     */
    private $pedigreeRegister;
    
    
    public function __construct($userAccount, $actionBy, $userActionType, $isCompleted = false, $description = null, $isUserEnvironment = true)
    {
        $this->logDate = new \DateTime();
        $this->isUserEnvironment = $isUserEnvironment;
        $this->userAccount = $userAccount;
        $this->actionBy = $actionBy;
        $this->userActionType = $userActionType;
        $this->isCompleted = $isCompleted;
        $this->description = $description;
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
     * @return Person
     */
    public function getMeasurementType()
    {
        return $this->measurementType;
    }

    /**
     * @param Person $measurementType
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