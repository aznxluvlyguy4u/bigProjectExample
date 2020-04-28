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
 * Class InbreedingCoefficientTaskReport
 * @ORM\Table(name="inbreeding_coefficient_task_report")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InbreedingCoefficientTaskReportRepository")
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskReport
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
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $workerId;


    /**
     * @var array|int[]
     * @ORM\Column(type="simple_array", nullable=false)
     */
    private $ramIds;

    /**
     * @var array|int[]
     * @ORM\Column(type="simple_array", nullable=false)
     */
    private $eweIds;

    /**
     * InbreedingCoefficientTaskReport constructor.
     * @param  int  $workerId
     * @param  array|int[]  $ramIds
     * @param  array|int[]  $eweIds
     */
    public function __construct(int $workerId, array $ramIds, array $eweIds)
    {
        $this->workerId = $workerId;
        $this->ramIds = $ramIds;
        $this->eweIds = $eweIds;
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
     * @return InbreedingCoefficientTaskReport
     */
    public function setId(int $id): InbreedingCoefficientTaskReport
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    /**
     * @param  int  $workerId
     * @return InbreedingCoefficientTaskReport
     */
    public function setWorkerId(int $workerId): InbreedingCoefficientTaskReport
    {
        $this->workerId = $workerId;
        return $this;
    }

    /**
     * @return array|int[]
     */
    public function getRamIds(): array
    {
        return $this->ramIds;
    }

    /**
     * @param  array|int[]  $ramIds
     * @return InbreedingCoefficientTaskReport
     */
    public function setRamIds($ramIds)
    {
        $this->ramIds = $ramIds;
        return $this;
    }

    /**
     * @return array|int[]
     */
    public function getEweIds(): array
    {
        return $this->eweIds;
    }

    /**
     * @param  array|int[]  $eweIds
     * @return InbreedingCoefficientTaskReport
     */
    public function setEweIds($eweIds)
    {
        $this->eweIds = $eweIds;
        return $this;
    }


}
