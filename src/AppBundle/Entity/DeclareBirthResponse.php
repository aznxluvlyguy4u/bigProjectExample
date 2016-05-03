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
  private $declareBirthRequestMessage;

    /**
     * Set DeclareBirthRequestMessage
     *
     * @param \AppBundle\Entity\DeclareBirth $declareBirthRequestMessage
     *
     * @return DeclareBirthResponse
     */
    public function setDeclareBirthRequestMessage(\AppBundle\Entity\DeclareBirth $declareBirthRequestMessage = null)
    {
        $this->declareBirthRequestMessage = $declareBirthRequestMessage;

        return $this;
    }

    /**
     * Get DeclareBirthRequestMessage
     *
     * @return \AppBundle\Entity\DeclareBirth
     */
    public function getDeclareBirthRequestMessage()
    {
        return $this->declareBirthRequestMessage;
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
