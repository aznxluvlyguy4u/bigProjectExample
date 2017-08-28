<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
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
class DeclareBirthResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

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
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $gender;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnNumber;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnCountryCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $ulnFather;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $ulnCountryCodeFather;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnMother;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnCountryCodeMother;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnSurrogate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnCountryCodeSurrogate;

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
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return DeclareBirthResponse
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return DeclareBirthResponse
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * Set ulnFather
     *
     * @param string $ulnFather
     *
     * @return DeclareBirthResponse
     */
    public function setUlnFather($ulnFather)
    {
        $this->ulnFather = $ulnFather;

        return $this;
    }

    /**
     * Get ulnFather
     *
     * @return string
     */
    public function getUlnFather()
    {
        return $this->ulnFather;
    }

    /**
     * Set ulnCountryCodeFather
     *
     * @param string $ulnCountryCodeFather
     *
     * @return DeclareBirthResponse
     */
    public function setUlnCountryCodeFather($ulnCountryCodeFather)
    {
        $this->ulnCountryCodeFather = $ulnCountryCodeFather;

        return $this;
    }

    /**
     * Get ulnCountryCodeFather
     *
     * @return string
     */
    public function getUlnCountryCodeFather()
    {
        return $this->ulnCountryCodeFather;
    }

    /**
     * Set ulnMother
     *
     * @param string $ulnMother
     *
     * @return DeclareBirthResponse
     */
    public function setUlnMother($ulnMother)
    {
        $this->ulnMother = $ulnMother;

        return $this;
    }

    /**
     * Get ulnMother
     *
     * @return string
     */
    public function getUlnMother()
    {
        return $this->ulnMother;
    }

    /**
     * Set ulnCountryCodeMother
     *
     * @param string $ulnCountryCodeMother
     *
     * @return DeclareBirthResponse
     */
    public function setUlnCountryCodeMother($ulnCountryCodeMother)
    {
        $this->ulnCountryCodeMother = $ulnCountryCodeMother;

        return $this;
    }

    /**
     * Get ulnCountryCodeMother
     *
     * @return string
     */
    public function getUlnCountryCodeMother()
    {
        return $this->ulnCountryCodeMother;
    }

    /**
     * Set ulnSurrogate
     *
     * @param string $ulnSurrogate
     *
     * @return DeclareBirthResponse
     */
    public function setUlnSurrogate($ulnSurrogate)
    {
        $this->ulnSurrogate = $ulnSurrogate;

        return $this;
    }

    /**
     * Get ulnSurrogate
     *
     * @return string
     */
    public function getUlnSurrogate()
    {
        return $this->ulnSurrogate;
    }

    /**
     * Set ulnCountryCodeSurrogate
     *
     * @param string $ulnCountryCodeSurrogate
     *
     * @return DeclareBirthResponse
     */
    public function setUlnCountryCodeSurrogate($ulnCountryCodeSurrogate)
    {
        $this->ulnCountryCodeSurrogate = $ulnCountryCodeSurrogate;

        return $this;
    }

    /**
     * Get ulnCountryCodeSurrogate
     *
     * @return string
     */
    public function getUlnCountryCodeSurrogate()
    {
        return $this->ulnCountryCodeSurrogate;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }


}
