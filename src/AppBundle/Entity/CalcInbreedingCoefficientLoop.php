<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * This calc table is only used for storing temporary data for calculations.
 * So no Java Hibernate class is necessary, if no relations to normal tables are used.
 * Quick writes are necessary, so no index is used.
 *
 * Class CalcInbreedingCoefficientLoop
 * @ORM\Table(name="calc_inbreeding_coefficient_loop")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CalcInbreedingCoefficientLoopRepository")
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientLoop
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
     * @ORM\Column(type="integer", nullable=false)
     */
    private $loopSize;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    private $lastParentId;

    /**
     * @var string
     * @ORM\Column(type="float", nullable=true)
     */
    private $parentInbreedingCoefficient;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $origin1Path;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $origin2Path;

    /**
     * @var array
     * @ORM\Column(type="simple_array", nullable=false)
     */
    private $origin1Parents;

    /**
     * @var array
     * @ORM\Column(type="simple_array", nullable=false)
     */
    private $origin2Parents;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return CalcInbreedingCoefficientLoop
     */
    public function setId(int $id): CalcInbreedingCoefficientLoop
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getLoopSize(): int
    {
        return $this->loopSize;
    }

    /**
     * @param  int  $loopSize
     * @return CalcInbreedingCoefficientLoop
     */
    public function setLoopSize(int $loopSize): CalcInbreedingCoefficientLoop
    {
        $this->loopSize = $loopSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastParentId(): int
    {
        return $this->lastParentId;
    }

    /**
     * @param  int  $lastParentId
     * @return CalcInbreedingCoefficientLoop
     */
    public function setLastParentId(int $lastParentId): CalcInbreedingCoefficientLoop
    {
        $this->lastParentId = $lastParentId;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentInbreedingCoefficient(): string
    {
        return $this->parentInbreedingCoefficient;
    }

    /**
     * @param  string  $parentInbreedingCoefficient
     * @return CalcInbreedingCoefficientLoop
     */
    public function setParentInbreedingCoefficient(string $parentInbreedingCoefficient): CalcInbreedingCoefficientLoop
    {
        $this->parentInbreedingCoefficient = $parentInbreedingCoefficient;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrigin1Path(): string
    {
        return $this->origin1Path;
    }

    /**
     * @param  string  $origin1Path
     * @return CalcInbreedingCoefficientLoop
     */
    public function setOrigin1Path(string $origin1Path): CalcInbreedingCoefficientLoop
    {
        $this->origin1Path = $origin1Path;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrigin2Path(): string
    {
        return $this->origin2Path;
    }

    /**
     * @param  string  $origin2Path
     * @return CalcInbreedingCoefficientLoop
     */
    public function setOrigin2Path(string $origin2Path): CalcInbreedingCoefficientLoop
    {
        $this->origin2Path = $origin2Path;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrigin1Parents(): array
    {
        return $this->origin1Parents;
    }

    /**
     * @param  array  $origin1Parents
     * @return CalcInbreedingCoefficientLoop
     */
    public function setOrigin1Parents(array $origin1Parents): CalcInbreedingCoefficientLoop
    {
        $this->origin1Parents = $origin1Parents;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrigin2Parents(): array
    {
        return $this->origin2Parents;
    }

    /**
     * @param  array  $origin2Parents
     * @return CalcInbreedingCoefficientLoop
     */
    public function setOrigin2Parents(array $origin2Parents): CalcInbreedingCoefficientLoop
    {
        $this->origin2Parents = $origin2Parents;
        return $this;
    }


}
