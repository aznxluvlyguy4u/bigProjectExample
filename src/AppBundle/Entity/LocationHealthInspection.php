<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthInspectionResult;
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
     * @JMS\Exclude
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $inspectionId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $orderNumber;

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
     * @var datetime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $totalLeadTime;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $nextAction;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LocationHealthInspectionDirection", mappedBy="inspection")
     * @ORM\OrderBy({"directionDate" = "DESC"})
     * @JMS\Type("array")
     */
    private $directions;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="LocationHealthInspectionResult", mappedBy="inspection")
     * @JMS\Type("array")
     */
    private $results;

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
        $this->setInspectionId(Utils::generateTokenCode());
        $this->directions = new ArrayCollection();
        $this->results = new ArrayCollection();
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
    public function getInspectionId()
    {
        return $this->inspectionId;
    }

    /**
     * @param string $inspectionId
     */
    public function setInspectionId($inspectionId)
    {
        $this->inspectionId = $inspectionId;
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
     * @return DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
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
    public function getNextAction()
    {
        return $this->nextAction;
    }

    /**
     * @param string $nextAction
     */
    public function setNextAction($nextAction)
    {
        $this->nextAction = $nextAction;
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

    /**
     * @return ArrayCollection
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param ArrayCollection $results
     */
    public function setResults($results)
    {
        $this->results = $results;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }
}
