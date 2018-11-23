<?php

namespace AppBundle\Entity;
use AppBundle\Component\MessageBuilderBase;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\MessageType;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class LocationHealth
 *
 * @ORM\Table(name="message",indexes={@ORM\Index(name="receiver_idx", columns={"message_id", "receiver_id", "receiver_location_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MessageRepository")
 * @package AppBundle\Entity
 */
class Message
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
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


    /**
     * @return string
     */
    public function getNotificationMessageTranslationKey(): string
    {
        return empty($this->getType()) ? '' :  $this->getType() . MessageType::NOTIFICATION_MESSAGE_SUFFIX;
    }

    /**
     * @return array|null
     */
    public function getDataForFireBase(): ?array
    {
        if (empty($this->getData())) {
            return null;
        }

        $key = JsonInputConstant::DATA;
        $value = $this->getData();

        if (
            $this->getData() === MessageType::DECLARE_ARRIVAL ||
            $this->getData() === MessageType::DECLARE_DEPART
        ) {
            $key = JsonInputConstant::ULN;
            $value = $this->getData();


        } elseif (
            $this->getData() === MessageType::NEW_INVOICE &&
            !empty($this->getSubject()) && is_string($this->getSubject()) &&
            !empty($this->getMessage()) && is_string($this->getMessage())
        ) {
            $key = $this->getSubject();
            $value = $this->getMessage();
        }

        return [
            $key => $value
        ];
    }
}