<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\RequestStateType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveCountries
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveCountriesRepository")
 * @package AppBundle\Entity
 */
class RetrieveCountries {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $requestId;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $messageId;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $requestState;

    public function __construct() {
        $this->setLogDate(new \DateTime());
        $this->setRequestId(rand(999,999999));
        $this->setMessageId($this->getRequestId());
        $this->setRequestState(RequestStateType::OPEN);
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
     * @return RetrieveCountries
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
     * Set requestId
     *
     * @param string $requestId
     *
     * @return RetrieveCountries
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Get requestId
     *
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set messageId
     *
     * @param string $messageId
     *
     * @return RetrieveCountries
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * Get messageId
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set requestState
     *
     * @param string $requestState
     *
     * @return RetrieveCountries
     */
    public function setRequestState($requestState)
    {
        $this->requestState = $requestState;

        return $this;
    }

    /**
     * Get requestState
     *
     * @return string
     */
    public function getRequestState()
    {
        return $this->requestState;
    }
}
