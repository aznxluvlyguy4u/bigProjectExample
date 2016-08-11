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
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $inspectionType;

    /**
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $inspectionDate;

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
     * @return string
     */
    public function getInspectionType()
    {
        return $this->inspectionType;
    }

    /**
     * @param string $inspectionType
     */
    public function setInspectionType($inspectionType)
    {
        $this->inspectionType = $inspectionType;
    }

    /**
     * @return DateTime
     */
    public function getInspectionDate()
    {
        return $this->inspectionDate;
    }

    /**
     * @param DateTime $inspectionDate
     */
    public function setInspectionDate($inspectionDate)
    {
        $this->inspectionDate = $inspectionDate;
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