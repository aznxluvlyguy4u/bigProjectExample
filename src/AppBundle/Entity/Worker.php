<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Worker
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WorkerRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap(
 *   {
 *      "ReportWorker" = "ReportWorker"
 *   }
 * )
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                      "ReportWorker" : "AppBundle\Entity\ReportWorker"
 *                      },
 *     groups = {
 *     "BASIC"
 * })
 * @package AppBundle\Entity
 */
abstract class Worker
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
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $workerType;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person", fetch="LAZY")
     * @ORM\JoinColumn(name="owner_id", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     */
    private $owner;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="workers", fetch="LAZY")
     * @ORM\JoinColumn(name="action_by_id", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     */
    private $actionBy;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="workers", fetch="LAZY")
     * @ORM\JoinColumn(name="location_id", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $errorCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $errorMessage;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $startedAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $finishedAt;

    public function __construct()
    {
        $this->startedAt = new \DateTime();
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
     * @return Worker
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getWorkerType()
    {
        return $this->workerType;
    }

    /**
     * @param $workerType
     * @return Worker
     */
    protected function setWorkerType($workerType)
    {
        $this->workerType = $workerType;
        return $this;
    }

    /**
     * @return Person
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param $owner
     * @return Worker
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @return Person
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param $actionBy
     * @return Worker
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param $location
     * @return Worker
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setErrorCode($value)
    {
        $this->errorCode = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setErrorMessage($value)
    {
        $this->errorMessage = $value;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param DateTime $date
     * @return $this
     */
    public function setStartedAt(DateTime $date)
    {
        $this->startedAt = $date;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param DateTime $date
     * @return $this
     */
    public function setFinishedAt(DateTime $date)
    {
        $this->finishedAt = $date;
        return $this;
    }
}