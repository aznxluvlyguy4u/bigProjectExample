<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * For the InbreedingCoefficient worker a separate table is used,
 * because the message needs to store different data,
 * and because a different worker needs to be used.
 *
 * Class InbreedingCoefficientTaskAdmin
 * @ORM\Table(name="inbreeding_coefficient_task_admin")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InbreedingCoefficientTaskAdminRepository")
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskAdmin
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
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    private $startedAt;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $year;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $month;

    /**
     * InbreedingCoefficientTaskAdmin constructor.
     * @param  int  $year
     * @param  int  $month
     * @param  DateTime  $startedAt
     */
    public function __construct(int $year, int $month, DateTime $startedAt)
    {
        $this->year = $year;
        $this->month = $month;
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
     * @return InbreedingCoefficientTaskAdmin
     */
    public function setId(int $id): InbreedingCoefficientTaskAdmin
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    /**
     * @param  DateTime|null  $startedAt
     * @return InbreedingCoefficientTaskAdmin
     */
    public function setStartedAt(?DateTime $startedAt): InbreedingCoefficientTaskAdmin
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @param  int  $year
     * @return InbreedingCoefficientTaskAdmin
     */
    public function setYear(int $year): InbreedingCoefficientTaskAdmin
    {
        $this->year = $year;
        return $this;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @param  int  $month
     * @return InbreedingCoefficientTaskAdmin
     */
    public function setMonth(int $month): InbreedingCoefficientTaskAdmin
    {
        $this->month = $month;
        return $this;
    }


}
