<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareBirth;

/**
 * Class DeclareBirthResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBirthResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareBirthResponse extends DeclareBaseResponse {

  /**
   * @var DeclareBirth
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareBirth", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareBirth")
   */
  private $DeclareBirthRequestMessage;
//JColumn(name="declare_birth_request_message_id", referencedColumnName="id")

    /**
     * Set DeclareBirthRequestMessage
     *
     * @param \AppBundle\Entity\DeclareBirth $DeclareBirthRequestMessage
     *
     * @return DeclareBirthResponse
     */
    public function setDeclareBirthRequestMessage(\AppBundle\Entity\DeclareBirth $DeclareBirthRequestMessage = null)
    {
        $this->DeclareBirthRequestMessage = $DeclareBirthRequestMessage;

        return $this;
    }

    /**
     * Get DeclareBirthRequestMessage
     *
     * @return \AppBundle\Entity\DeclareBirth
     */
    public function getDeclareBirthRequestMessage()
    {
        return $this->DeclareBirthRequestMessage;
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
     * @return DeclareBirthResponse
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
