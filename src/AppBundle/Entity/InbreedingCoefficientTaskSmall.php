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
 * Class InbreedingCoefficientTaskSmall
 * @ORM\Table(name="inbreeding_coefficient_task_small")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InbreedingCoefficientTaskSmallRepository")
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskSmall
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
     * @ORM\Column(type="integer", nullable=false)
     */
    private $ramId;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $eweId;

    /**
     * InbreedingCoefficientTaskSmall constructor.
     * @param  int  $ramId
     * @param  int  $eweId
     */
    public function __construct(int $ramId, int $eweId)
    {
        $this->ramId = $ramId;
        $this->eweId = $eweId;
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
     * @return InbreedingCoefficientTaskSmall
     */
    public function setId(int $id): InbreedingCoefficientTaskSmall
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getRamId(): int
    {
        return $this->ramId;
    }

    /**
     * @param  int  $ramId
     * @return InbreedingCoefficientTaskSmall
     */
    public function setRamId(int $ramId): InbreedingCoefficientTaskSmall
    {
        $this->ramId = $ramId;
        return $this;
    }

    /**
     * @return int
     */
    public function getEweId(): int
    {
        return $this->eweId;
    }

    /**
     * @param  int  $eweId
     * @return InbreedingCoefficientTaskSmall
     */
    public function setEweId(int $eweId): InbreedingCoefficientTaskSmall
    {
        $this->eweId = $eweId;
        return $this;
    }


}
