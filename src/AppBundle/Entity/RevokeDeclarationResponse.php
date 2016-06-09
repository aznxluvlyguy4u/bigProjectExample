<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\RevokeDeclaration;

/**
 * Class RevokeDeclarationResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RevokeDeclarationResponseRepository")
 * @package AppBundle\Entity
 */
class RevokeDeclarationResponse extends DeclareBaseResponse {

  /**
   * @var RevokeDeclaration
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="RevokeDeclaration", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
   */
  private $revokeDeclarationRequestMessage;
//JColumn(name="revoke_declaration_request_message_id", referencedColumnName="id")

    /**
     * Set revokeDeclarationRequestMessage
     *
     * @param \AppBundle\Entity\RevokeDeclaration $revokeDeclarationRequestMessage
     *
     * @return RevokeDeclarationResponse
     */
    public function setRevokeDeclarationRequestMessage(\AppBundle\Entity\RevokeDeclaration $revokeDeclarationRequestMessage = null)
    {
        $this->revokeDeclarationRequestMessage = $revokeDeclarationRequestMessage;

        return $this;
    }

    /**
     * Get revokeDeclarationRequestMessage
     *
     * @return \AppBundle\Entity\RevokeDeclaration
     */
    public function getRevokeDeclarationRequestMessage()
    {
        return $this->revokeDeclarationRequestMessage;
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return RevokeDeclarationResponse
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
}
