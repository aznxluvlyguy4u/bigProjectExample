<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareDepart;

/**
 * Class DeclareDepartResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareDepartResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareDepartResponse extends DeclareBaseResponse {

  /**
   * @var DeclareDepart
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareDepart", cascade={"persist"}, inversedBy="responses") TODO check this <-
   * @JMS\Type("AppBundle\Entity\DeclareDepart")
   */
  private $DeclareDepartRequestMessage;
//JColumn(name="declare_depart_request_message_id", referencedColumnName="id")

    /**
     * Set DeclareDepartRequestMessage
     *
     * @param \AppBundle\Entity\DeclareDepart $DeclareDepartRequestMessage
     *
     * @return DeclareDepartResponse
     */
    public function setDeclareDepartRequestMessage(\AppBundle\Entity\DeclareDepart $DeclareDepartRequestMessage = null)
    {
        $this->DeclareDepartRequestMessage = $DeclareDepartRequestMessage;

        return $this;
    }

    /**
     * Get DeclareDepartRequestMessage
     *
     * @return \AppBundle\Entity\DeclareDepart
     */
    public function getDeclareDepartRequestMessage()
    {
        return $this->DeclareDepartRequestMessage;
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
     * @return DeclareDepartResponse
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
