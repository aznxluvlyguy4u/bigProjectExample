<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareLossResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareLossResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareLossResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

    /**
     * @var DeclareLoss
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareLoss", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareLoss")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $declareLossRequestMessage;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $dateOfDeath;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 20)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $reasonOfLoss;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ubnDestructor;


    /**
     * Set declareLossRequestMessage
     *
     * @param \AppBundle\Entity\DeclareLoss $declareLossRequestMessage
     *
     * @return DeclareLossResponse
     */
    public function setDeclareLossRequestMessage(\AppBundle\Entity\DeclareLoss $declareLossRequestMessage = null)
    {
        $this->declareLossRequestMessage = $declareLossRequestMessage;

        return $this;
    }

    /**
     * Get declareLossRequestMessage
     *
     * @return \AppBundle\Entity\DeclareLoss
     */
    public function getDeclareLossRequestMessage()
    {
        return $this->declareLossRequestMessage;
    }


    /**
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * @param string $ulnCountryCode
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;
    }

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfDeath()
    {
        return $this->dateOfDeath;
    }

    /**
     * @param \DateTime $dateOfDeath
     */
    public function setDateOfDeath($dateOfDeath)
    {
        $this->dateOfDeath = $dateOfDeath;
    }

    /**
     * @return string
     */
    public function getReasonOfLoss()
    {
        return $this->reasonOfLoss;
    }

    /**
     * @param string $reasonOfLoss
     */
    public function setReasonOfLoss($reasonOfLoss)
    {
        $this->reasonOfLoss = $reasonOfLoss;
    }

    /**
     * @return string
     */
    public function getUbnDestructor()
    {
        return $this->ubnDestructor;
    }

    /**
     * @param string $ubnDestructor
     */
    public function setUbnDestructor($ubnDestructor)
    {
        $this->ubnDestructor = $ubnDestructor;
    }

    /**
     * @param DeclareLoss $loss
     * @return DeclareLossResponse
     */
    public function setDeclareLossIncludingAllValues(DeclareLoss $loss): DeclareLossResponse
    {
        $this->setDeclareBaseValues($loss);
        $this->setDeclareLossRequestMessage($loss);
        $this->setDateOfDeath($loss->getDateOfDeath());
        $this->setUbnDestructor($loss->getUbnDestructor());
        $this->setReasonOfLoss($loss->getReasonOfLoss());
        $this->setUlnCountryCode($loss->getUlnCountryCode());
        $this->setUlnNumber($loss->getUlnNumber());
        return $this;
    }

}
