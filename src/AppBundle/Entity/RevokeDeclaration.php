<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class RevokeDeclaration
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RevokeDeclarationRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class RevokeDeclaration extends DeclareBase
{

    /**
     * @ORM\OneToMany(targetEntity="RevokeDeclarationResponse", mappedBy="revokeDeclarationRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_declaration_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     * @Expose
     */
    private $responses;

    /**
     * constructor.
     */
    public function __construct() {
        parent::__construct();

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return RevokeDeclaration
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
     * Add response
     *
     * @param \AppBundle\Entity\RevokeDeclarationResponse $response
     *
     * @return RevokeDeclaration
     */
    public function addResponse(\AppBundle\Entity\RevokeDeclarationResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\RevokeDeclarationResponse $response
     */
    public function removeResponse(\AppBundle\Entity\RevokeDeclarationResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }
}
