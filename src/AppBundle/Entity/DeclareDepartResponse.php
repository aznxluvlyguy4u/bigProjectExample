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
   * @ORM\ManyToOne(targetEntity="DeclareDepart", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareDepart")
   */
  private $declareDepartRequestMessage;
//JColumn(name="declare_depart_request_message_id", referencedColumnName="id")

    /**
     * Set DeclareDepartRequestMessage
     *
     * @param \AppBundle\Entity\DeclareDepart $DeclareDepartRequestMessage
     *
     * @return DeclareDepartResponse
     */
    public function setDeclareDepartRequestMessage(\AppBundle\Entity\DeclareDepart $declareDepartRequestMessage = null)
    {
        $this->declareDepartRequestMessage = $declareDepartRequestMessage;

        return $this;
    }

    /**
     * Get DeclareDepartRequestMessage
     *
     * @return \AppBundle\Entity\DeclareDepart
     */
    public function getDeclareDepartRequestMessage()
    {
        return $this->declareDepartRequestMessage;
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
     * @return DeclareDepartResponse
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
}
