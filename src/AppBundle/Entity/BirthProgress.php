<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BirthProgress
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BirthProgressRepository")
 * @package AppBundle\Entity
 */
class BirthProgress
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
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    private $description;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @Assert\Type("string")
     */
    private $dutchDescription;


    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     * @JMS\Type("integer")
     * @Assert\NotBlank
     * @Assert\Type("integer")
     */
    private $mixBlupScore;


    /**
     * BirthProgress constructor.
     * @param string $description
     * @param string $dutchDescription
     * @param int $mixBlupScore
     */
    public function __construct($description, $dutchDescription, $mixBlupScore)
    {
        $this->description = $description;
        $this->dutchDescription = $dutchDescription;
        $this->mixBlupScore = $mixBlupScore;
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
     * @return BirthProgress
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return BirthProgress
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDutchDescription()
    {
        return $this->dutchDescription;
    }

    /**
     * @param string $dutchDescription
     * @return BirthProgress
     */
    public function setDutchDescription($dutchDescription)
    {
        $this->dutchDescription = $dutchDescription;
        return $this;
    }

    /**
     * @return int
     */
    public function getMixBlupScore()
    {
        return $this->mixBlupScore;
    }

    /**
     * @param int $mixBlupScore
     * @return BirthProgress
     */
    public function setMixBlupScore($mixBlupScore)
    {
        $this->mixBlupScore = $mixBlupScore;
        return $this;
    }

    
}