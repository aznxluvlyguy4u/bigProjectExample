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
 * Class CalcInbreedingCoefficientParentDetails
 * @ORM\Table(name="calc_inbreeding_coefficient_parent_details")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CalcInbreedingCoefficientParentDetailsRepository")
 * @package AppBundle\Entity
 */
class CalcInbreedingCoefficientParentDetails
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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setId(int $id): CalcInbreedingCoefficientParentDetails
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
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setAnimalId(int $animalId): CalcInbreedingCoefficientParentDetails
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
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setDateOfBirth(DateTime $dateOfBirth): CalcInbreedingCoefficientParentDetails
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
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setParentId(int $parentId): CalcInbreedingCoefficientParentDetails
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
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setParentDateOfBirth(DateTime $parentDateOfBirth): CalcInbreedingCoefficientParentDetails
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
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setParentType(string $parentType): CalcInbreedingCoefficientParentDetails
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
     * @return CalcInbreedingCoefficientParentDetails
     */
    public function setParentInbreedingCoefficient(string $parentInbreedingCoefficient
    ): CalcInbreedingCoefficientParentDetails {
        $this->parentInbreedingCoefficient = $parentInbreedingCoefficient;
        return $this;
    }


}
