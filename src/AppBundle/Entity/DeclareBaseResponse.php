<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;

/**
 * Class DeclareBaseResponse
 * @ORM\Entity
 * @package AppBundle\Entity
 */
class DeclareBaseResponse
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 20)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $messageId;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $date;

    /**
     * @var string;
     *
     * @ORM\Column(type="string", nullable=true)
     *
     */
    private $errorCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $errorMessage;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     */
    private $errorKindIndicator;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 1)
     */
    private $successIndicator;

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
     * Set messageId
     *
     * @param string $messageId
     *
     * @return DeclareBaseResponse
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return DeclareBaseResponse
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set errorCode
     *
     * @param string $errorCode
     *
     * @return DeclareBaseResponse
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set errorMessage
     *
     * @param string $errorMessage
     *
     * @return DeclareBaseResponse
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Get errorMessage
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set errorKindIndicator
     *
     * @param string $errorKindIndicator
     *
     * @return DeclareBaseResponse
     */
    public function setErrorKindIndicator($errorKindIndicator)
    {
        $this->errorKindIndicator = $errorKindIndicator;

        return $this;
    }

    /**
     * Get errorKindIndicator
     *
     * @return string
     */
    public function getErrorKindIndicator()
    {
        return $this->errorKindIndicator;
    }

    /**
     * Set successIndicator
     *
     * @param string $successIndicator
     *
     * @return DeclareBaseResponse
     */
    public function setSuccessIndicator($successIndicator)
    {
        $this->successIndicator = $successIndicator;

        return $this;
    }

    /**
     * Get successIndicator
     *
     * @return string
     */
    public function getSuccessIndicator()
    {
        return $this->successIndicator;
    }
}
