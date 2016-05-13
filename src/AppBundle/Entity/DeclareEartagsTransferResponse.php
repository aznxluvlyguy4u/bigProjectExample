<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclareEartagsTransferResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareEartagsTransferResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareEartagsTransferResponse extends DeclareBaseResponse {

  /**
   * @var DeclareEartagsTransfer
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareEartagsTransfer", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareEartagsTransfer")
   */
  private $declareEartagsTransferRequestMessage;


    /**
     * Set declareEartagsTransferRequestMessage
     *
     * @param \AppBundle\Entity\DeclareEartagsTransfer $declareEartagsTransferRequestMessage
     *
     * @return DeclareEartagsTransferResponse
     */
    public function setDeclareEartagsTransferRequestMessage(\AppBundle\Entity\DeclareEartagsTransfer $declareEartagsTransferRequestMessage = null)
    {
        $this->declareEartagsTransferRequestMessage = $declareEartagsTransferRequestMessage;

        return $this;
    }

    /**
     * Get declareEartagsTransferRequestMessage
     *
     * @return \AppBundle\Entity\DeclareEartagsTransfer
     */
    public function getDeclareEartagsTransferRequestMessage()
    {
        return $this->declareEartagsTransferRequestMessage;
    }
}
