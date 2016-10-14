<?php

namespace AppBundle\Entity;
use AppBundle\Component\MessageBuilderBase;
use AppBundle\Enumerator\MessageType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use DateTime;

/**
 * Class LocationHealth
 *
 * @ORM\Table(name="message",indexes={@ORM\Index(name="receiver_idx", columns={"message_id", "receiver_id", "receiver_location_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MessageRepository")
 * @package AppBundle\Entity
 */
class Message
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $messageId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @JMS\Type("Person")
     * @ORM\JoinColumn(name="sender_id", referencedColumnName="id", nullable=true)
     */
    private $sender;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location")
     * @JMS\Type("Location")
     * @ORM\JoinColumn(name="sender_location_id", referencedColumnName="id", nullable=true)
     */
    private $senderLocation;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @JMS\Type("Person")
     * @ORM\JoinColumn(name="receiver_id", referencedColumnName="id", nullable=true)
     */
    private $receiver;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location")
     * @JMS\Type("Location")
     * @ORM\JoinColumn(name="receiver_location_id", referencedColumnName="id", nullable=true)
     */
    private $receiverLocation;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $data;

    /**
     * @var DeclareBase
     *
     * @ORM\OneToOne(targetEntity="DeclareBase")
     * @JMS\Type("DeclareBase")
     * @ORM\JoinColumn(name="declare_base_id", referencedColumnName="id", nullable=true)
     */
    private $requestMessage;

    /**
     * @var DeclareBaseResponse
     *
     * @ORM\OneToOne(targetEntity="DeclareBaseResponse")
     * @JMS\Type("DeclareBaseResponse")
     * @ORM\JoinColumn(name="declare_base_response_id", referencedColumnName="id", nullable=true)
     */
    private $responseMessage;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    private $isRead;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    private $isHidden;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $creationDate;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->isHidden = true;
        $this->isRead = false;
        $this->type = MessageType::USER;
        $this->setMessageId(MessageBuilderBase::getNewRequestId());
    }

    /**
     * @param string $messageId
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return Person
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param Person $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return Location
     */
    public function getSenderLocation()
    {
        return $this->senderLocation;
    }

    /**
     * @param Location $senderLocation
     */
    public function setSenderLocation($senderLocation)
    {
        $this->senderLocation = $senderLocation;
    }

    /**
     * @return Person
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @param Person $receiver
     */
    public function setReceiver($receiver)
    {
        $this->receiver = $receiver;
    }

    /**
     * @return Location
     */
    public function getReceiverLocation()
    {
        return $this->receiverLocation;
    }

    /**
     * @param Location $receiverLocation
     */
    public function setReceiverLocation($receiverLocation)
    {
        $this->receiverLocation = $receiverLocation;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return DeclareBase
     */
    public function getRequestMessage()
    {
        return $this->requestMessage;
    }

    /**
     * @param DeclareBase $requestMessage
     */
    public function setRequestMessage($requestMessage)
    {
        $this->requestMessage = $requestMessage;
    }

    /**
     * @return DeclareBaseResponse
     */
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }

    /**
     * @param DeclareBaseResponse $responseMessage
     */
    public function setResponseMessage($responseMessage)
    {
        $this->responseMessage = $responseMessage;
    }

    /**
     * @return boolean
     */
    public function isRead()
    {
        return $this->isRead;
    }

    /**
     * @param boolean $isRead
     */
    public function setRead($isRead)
    {
        $this->isRead = $isRead;
    }

    /**
     * @return boolean
     */
    public function isHidden()
    {
        return $this->isHidden;
    }

    /**
     * @param boolean $isHidden
     */
    public function setHidden($isHidden)
    {
        $this->isHidden = $isHidden;
    }

    /**
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }
}