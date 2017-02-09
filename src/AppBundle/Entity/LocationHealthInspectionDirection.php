<?php

namespace AppBundle\Entity;

use \DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LocationHealthInspectionDirection
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthInspectionDirectionRepository")
 * @package AppBundle\Entity
 */
class LocationHealthInspectionDirection
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Exclude
     */
    protected $id;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="Employee")
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $actionTakenBy;

    /**
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $directionDate;


    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $directionType;


    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="LocationHealthInspection", inversedBy="directions", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\LocationHealthInspection")
     */
    protected $inspection;

    /**
     * LocationHealthInspectionDirection constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Employee
     */
    public function getActionTakenBy()
    {
        return $this->actionTakenBy;
    }

    /**
     * @param Employee $actionTakenBy
     */
    public function setActionTakenBy($actionTakenBy)
    {
        $this->actionTakenBy = $actionTakenBy;
    }

    /**
     * @return string
     */
    public function getDirectionType()
    {
        return $this->directionType;
    }

    /**
     * @param string $directionType
     */
    public function setDirectionType($directionType)
    {
        $this->directionType = $directionType;
    }

    /**
     * @return DateTime
     */
    public function getDirectionDate()
    {
        return $this->directionDate;
    }

    /**
     * @param DateTime $directionDate
     */
    public function setDirectionDate($directionDate)
    {
        $this->directionDate = $directionDate;
    }

    /**
     * @return mixed
     */
    public function getInspection()
    {
        return $this->inspection;
    }

    /**
     * @param mixed $inspection
     */
    public function setInspection($inspection)
    {
        $this->inspection = $inspection;
    }

}