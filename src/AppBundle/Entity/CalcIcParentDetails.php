<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * This calc table is only used for storing temporary data for calculations.
 * So no Java Hibernate class is necessary, if no relations to normal tables are used.
 * Quick writes are necessary, so no index is used.
 *
 * Class CalcIcParentDetails
 * @ORM\Table(name="calc_ic_parent_details")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CalcIcParentDetailsRepository")
 * @package AppBundle\Entity
 */
class CalcIcParentDetails
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
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $dateOfBirth;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    private $parentId;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $parentDateOfBirth;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $parentType;

    /**
     * @var string
     * @ORM\Column(type="float", nullable=true)
     */
    private $parentInbreedingCoefficient;

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
     * @return CalcIcParentDetails
     */
    public function setId(int $id): CalcIcParentDetails
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
     * @return CalcIcParentDetails
     */
    public function setAnimalId(int $animalId): CalcIcParentDetails
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateOfBirth(): DateTime
    {
        return $this->dateOfBirth;
    }

    /**
     * @param  DateTime  $dateOfBirth
     * @return CalcIcParentDetails
     */
    public function setDateOfBirth(DateTime $dateOfBirth): CalcIcParentDetails
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return $this->parentId;
    }

    /**
     * @param  int  $parentId
     * @return CalcIcParentDetails
     */
    public function setParentId(int $parentId): CalcIcParentDetails
    {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getParentDateOfBirth(): DateTime
    {
        return $this->parentDateOfBirth;
    }

    /**
     * @param  DateTime  $parentDateOfBirth
     * @return CalcIcParentDetails
     */
    public function setParentDateOfBirth(DateTime $parentDateOfBirth): CalcIcParentDetails
    {
        $this->parentDateOfBirth = $parentDateOfBirth;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentType(): string
    {
        return $this->parentType;
    }

    /**
     * @param  string  $parentType
     * @return CalcIcParentDetails
     */
    public function setParentType(string $parentType): CalcIcParentDetails
    {
        $this->parentType = $parentType;
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
     * @return CalcIcParentDetails
     */
    public function setParentInbreedingCoefficient(string $parentInbreedingCoefficient
    ): CalcIcParentDetails {
        $this->parentInbreedingCoefficient = $parentInbreedingCoefficient;
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
     * @return CalcIcParentDetails
     */
    public function setIsPrimaryAnimal(bool $isPrimaryAnimal): CalcIcParentDetails
    {
        $this->isPrimaryAnimal = $isPrimaryAnimal;
        return $this;
    }


}
