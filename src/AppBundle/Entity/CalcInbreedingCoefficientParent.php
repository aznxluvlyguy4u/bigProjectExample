<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * This calc table is only used for storing temporary data for calculations.
 * So no Java Hibernate class is necessary, if no relations to normal tables are used.
 * Quick writes are necessary, so no index is used.
 *
 * Class CalcInbreedingCoefficientParent
 * @ORM\Table(name="calc_inbreeding_coefficient_parent")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CalcInbreedingCoefficientParentRepository")
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientParent
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
    private $animalId;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isPrimaryAnimal;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return CalcInbreedingCoefficientParent
     */
    public function setId(int $id): CalcInbreedingCoefficientParent
    {
        $this->id = $id;
        return $this;
    }


    /**
     * @return int
     */
    public function getAnimalId(): int
    {
        return $this->animalId;
    }

    /**
     * @param  int  $animalId
     * @return CalcInbreedingCoefficientParent
     */
    public function setAnimalId(int $animalId): CalcInbreedingCoefficientParent
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPrimaryAnimal(): bool
    {
        return $this->isPrimaryAnimal;
    }

    /**
     * @param  bool  $isPrimaryAnimal
     * @return CalcInbreedingCoefficientParent
     */
    public function setIsPrimaryAnimal(bool $isPrimaryAnimal): CalcInbreedingCoefficientParent
    {
        $this->isPrimaryAnimal = $isPrimaryAnimal;
        return $this;
    }



}
