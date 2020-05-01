<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * This calc table is only used for storing temporary data for calculations.
 * So no Java Hibernate class is necessary, if no relations to normal tables are used.
 * Quick writes are necessary, so no index is used.
 *
 * Class CalcIcParent
 * @ORM\Table(name="calc_ic_parent")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CalcIcParentRepository")
 * @package AppBundle\Entity
 */
class CalcIcParent
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
     * @return CalcIcParent
     */
    public function setId(int $id): CalcIcParent
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
     * @return CalcIcParent
     */
    public function setAnimalId(int $animalId): CalcIcParent
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
     * @return CalcIcParent
     */
    public function setIsPrimaryAnimal(bool $isPrimaryAnimal): CalcIcParent
    {
        $this->isPrimaryAnimal = $isPrimaryAnimal;
        return $this;
    }



}
