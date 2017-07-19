<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class AnimalMedicine
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalMedicineRepository")
 * @package AppBundle\Entity
 */
class AnimalComment
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $creationDate;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     */
    private $animal;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $comment;

    /**
     * AnimalMedicine constructor.
     */
    public function __construct()
    {
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
     * @return AnimalMedicine
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     * @return AnimalMedicine
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfUse()
    {
        return $this->dateOfUse;
    }

    /**
     * @param \DateTime $dateOfUse
     * @return AnimalMedicine
     */
    public function setDateOfUse($dateOfUse)
    {
        $this->dateOfUse = $dateOfUse;
        return $this;
    }

    /**
     * @return AnimalMedicineType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param AnimalMedicineType $type
     * @return AnimalMedicine
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return AnimalMedicine
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return AnimalMedicine
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }


}