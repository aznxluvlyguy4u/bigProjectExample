<?php

namespace AppBundle\Entity;

use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Location;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LocationHealthInspection
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationHealthInspectionRepository")
 * @package AppBundle\Entity
 */
class LocationHealthInspection
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
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="inspections")
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $inspectionSubject;

    /**
     * @var datetime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $requestDate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $totalLeadTime;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LocationHealthInspectionDirection", mappedBy="inspection")
     * @JMS\Type("array")
     */
    private $directions;

    /**
     * @var Employee
     *
     * @ORM\ManyToOne(targetEntity="Employee", inversedBy="healthInspections", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $authorizedBy;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $status;

    /**
     * LocationHealthInspection constructor.
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
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param \AppBundle\Entity\Location $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getInspectionSubject()
    {
        return $this->inspectionSubject;
    }

    /**
     * @param string $inspectionSubject
     */
    public function setInspectionSubject($inspectionSubject)
    {
        $this->inspectionSubject = $inspectionSubject;
    }

    /**
     * @return DateTime
     */
    public function getRequestDate()
    {
        return $this->requestDate;
    }

    /**
     * @param DateTime $requestDate
     */
    public function setRequestDate($requestDate)
    {
        $this->requestDate = $requestDate;
    }

    /**
     * @return int
     */
    public function getTotalLeadTime()
    {
        return $this->totalLeadTime;
    }

    /**
     * @param int $totalLeadTime
     */
    public function setTotalLeadTime($totalLeadTime)
    {
        $this->totalLeadTime = $totalLeadTime;
    }

    /**
     * @return ArrayCollection
     */
    public function getDirections()
    {
        return $this->directions;
    }

    /**
     * @param ArrayCollection $directions
     */
    public function setDirections($directions)
    {
        $this->directions = $directions;
    }

    /**
     * @return Employee
     */
    public function getAuthorizedBy()
    {
        return $this->authorizedBy;
    }

    /**
     * @param Employee $authorizedBy
     */
    public function setAuthorizedBy($authorizedBy)
    {
        $this->authorizedBy = $authorizedBy;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
