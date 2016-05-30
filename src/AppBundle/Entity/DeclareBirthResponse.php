<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareBirth;

/**
 * Class DeclareBirthResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBirthResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareBirthResponse extends DeclareBaseResponse {

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $dateOfBirth;

    /**
     * @ORM\ManyToOne(targetEntity="Animal", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var Ram
     *
     * @ORM\ManyToOne(targetEntity="Ram", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_father_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Ram")
     */
    protected $parentFather;

    /**
     * @var Ewe
     *
     * @ORM\ManyToOne(targetEntity="Ewe", cascade={"persist"})
     * @ORM\JoinColumn(name="parent_mother_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
    protected $parentMother;

    /**
     * @var Ewe
     *
     * @ORM\ManyToOne(targetEntity="Ewe", cascade={"persist"})
     * @ORM\JoinColumn(name="surrogate_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
    protected $surrogate;

    /**
     * @var DeclareBirth
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareBirth", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareBirth")
     */
    private $declareBirthRequestMessage;

    /**
     * Set DeclareBirthRequestMessage
     *
     * @param \AppBundle\Entity\DeclareBirth $declareBirthRequestMessage
     *
     * @return DeclareBirthResponse
     */
    public function setDeclareBirthRequestMessage(\AppBundle\Entity\DeclareBirth $declareBirthRequestMessage = null)
    {
        $this->declareBirthRequestMessage = $declareBirthRequestMessage;

        return $this;
    }

    /**
     * Get DeclareBirthRequestMessage
     *
     * @return \AppBundle\Entity\DeclareBirth
     */
    public function getDeclareBirthRequestMessage()
    {
        return $this->declareBirthRequestMessage;
    }


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return DeclareBirthResponse
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return DeclareBirthResponse
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareBirthResponse
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set parentFather
     *
     * @param \AppBundle\Entity\Ram $parentFather
     *
     * @return DeclareBirthResponse
     */
    public function setParentFather(\AppBundle\Entity\Ram $parentFather = null)
    {
        $this->parentFather = $parentFather;

        return $this;
    }

    /**
     * Get parentFather
     *
     * @return \AppBundle\Entity\Ram
     */
    public function getParentFather()
    {
        return $this->parentFather;
    }

    /**
     * Set parentMother
     *
     * @param \AppBundle\Entity\Ewe $parentMother
     *
     * @return DeclareBirthResponse
     */
    public function setParentMother(\AppBundle\Entity\Ewe $parentMother = null)
    {
        $this->parentMother = $parentMother;

        return $this;
    }

    /**
     * Get parentMother
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getParentMother()
    {
        return $this->parentMother;
    }

    /**
     * Set surrogate
     *
     * @param \AppBundle\Entity\Ewe $surrogate
     *
     * @return DeclareBirthResponse
     */
    public function setSurrogate(\AppBundle\Entity\Ewe $surrogate = null)
    {
        $this->surrogate = $surrogate;

        return $this;
    }

    /**
     * Get surrogate
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getSurrogate()
    {
        return $this->surrogate;
    }
}
