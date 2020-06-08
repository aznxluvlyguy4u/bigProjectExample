<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class TaskResidenceFix
 * @ORM\Table(name="task_residence_fix")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TaskResidenceFixRepository")
 * @package AppBundle\Entity
 */
class TaskResidenceFix
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
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    private $startedAt;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $locationId;

    /**
     * TaskResidenceFix constructor.
     * @param  int  $locationId
     * @param  DateTime  $startedAt
     */
    public function __construct(int $locationId, DateTime $startedAt)
    {
        $this->locationId = $locationId;
        $this->startedAt = $startedAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return TaskResidenceFix
     */
    public function setId(int $id): TaskResidenceFix
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    /**
     * @param  DateTime  $startedAt
     * @return TaskResidenceFix
     */
    public function setStartedAt(DateTime $startedAt): TaskResidenceFix
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getLocationId(): int
    {
        return $this->locationId;
    }

    /**
     * @param int $locationId
     * @return TaskResidenceFix
     */
    public function setLocationId(int $locationId): TaskResidenceFix
    {
        $this->locationId = $locationId;
        return $this;
    }




}
