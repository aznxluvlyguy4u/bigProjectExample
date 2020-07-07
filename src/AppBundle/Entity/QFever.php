<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class QFever
 * @package AppBundle\Entity
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\QFeverRepository")
 */
class QFever
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
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * @Assert\NotNull
     */
    private $animalType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $flagType;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return QFever
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getAnimalType(): string
    {
        return $this->animalType;
    }

    /**
     * @param string $animalType
     * @return QFever
     */
    public function setAnimalType(string $animalType): self
    {
        $this->animalType = $animalType;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlagType(): string
    {
        return $this->flagType;
    }

    /**
     * @param string $flagType
     * @return QFever
     */
    public function setFlagType(string $flagType): self
    {
        $this->flagType = $flagType;

        return $this;
    }
}