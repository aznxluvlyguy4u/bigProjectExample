<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\TimeUtil;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class EmailChangeConfirmation
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BaseRepository")
 * @package AppBundle\Entity
 */
class EmailChangeConfirmation
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Person", inversedBy="emailChangeToken", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Person")
     * @Exclude
     */
    private $person;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     */
    protected $emailAddress;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     */
    protected $creationDate;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     */
    private $token;


    public function __construct()
    {
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
     * Set emailAddress
     *
     * @param string $emailAddress
     *
     * @return EmailChangeConfirmation
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = trim(strtolower($emailAddress));

        return $this;
    }

    /**
     * Get emailAddress
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     * @return EmailChangeConfirmation
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return EmailChangeConfirmation
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @param Person $person
     * @return EmailChangeConfirmation
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }


    /**
     * @return bool|int
     */
    public function getEmailConfirmationTokenAgeInDays()
    {
        return TimeUtil::getDaysBetween($this->getCreationDate(), new \DateTime());
    }
}
