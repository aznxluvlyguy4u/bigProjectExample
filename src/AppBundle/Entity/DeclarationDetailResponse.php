<?php

namespace AppBundle\Entity;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use \AppBundle\Entity\DeclarationDetail;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclarationDetailResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclarationDetailResponseRepository")
 * @package AppBundle\Entity
 */
class DeclarationDetailResponse extends DeclarationBaseResponse
{
    use EntityClassInfo;

    /**
     * @var DeclarationDetail
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclarationDetail", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclarationDetail")
     */
    private $declarationDetailRequestMessage;

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
     * @return DeclarationDetailResponse
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
     * @return DeclarationDetailResponse
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
     * @return DeclarationDetailResponse
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
     * @return DeclarationDetailResponse
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

    /**
     * Set relationNumberKeeper
     *
     * @param string $relationNumberKeeper
     *
     * @return DeclarationDetailResponse
     */
    public function setRelationNumberKeeper($relationNumberKeeper)
    {
        $this->relationNumberKeeper = $relationNumberKeeper;

        return $this;
    }

    /**
     * Get relationNumberKeeper
     *
     * @return string
     */
    public function getRelationNumberKeeper()
    {
        return $this->relationNumberKeeper;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclarationDetailResponse
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * Set declarationDetailRequestMessage
     *
     * @param \AppBundle\Entity\DeclarationDetail $declarationDetailRequestMessage
     *
     * @return DeclarationDetailResponse
     */
    public function setDeclarationDetailRequestMessage(\AppBundle\Entity\DeclarationDetail $declarationDetailRequestMessage = null)
    {
        $this->declarationDetailRequestMessage = $declarationDetailRequestMessage;

        return $this;
    }

    /**
     * Get declarationDetailRequestMessage
     *
     * @return \AppBundle\Entity\DeclarationDetail
     */
    public function getDeclarationDetailRequestMessage()
    {
        return $this->declarationDetailRequestMessage;
    }
}
