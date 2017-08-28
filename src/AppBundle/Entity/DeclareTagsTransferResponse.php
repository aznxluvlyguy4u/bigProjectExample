<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclareTagsTransferResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareTagsTransferResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareTagsTransferResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

  /**
   * @var DeclareTagsTransfer
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareTagsTransfer", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareTagsTransfer")
   */
  private $declareTagsTransferRequestMessage;


    /**
     * Set declareEartagsTransferRequestMessage
     *
     * @param \AppBundle\Entity\DeclareTagsTransfer $declareTagsTransferRequestMessage
     *
     * @return DeclareTagsTransferResponse
     */
    public function setDeclareTagsTransferRequestMessage(\AppBundle\Entity\DeclareTagsTransfer $declareTagsTransferRequestMessage = null)
    {
        $this->declareTagsTransferRequestMessage = $declareTagsTransferRequestMessage;

        return $this;
    }

    /**
     * Get declareEartagsTransferRequestMessage
     *
     * @return \AppBundle\Entity\DeclareTagsTransfer
     */
    public function getDeclareTagsTransferRequestMessage()
    {
        return $this->declareTagsTransferRequestMessage;
    }
}
