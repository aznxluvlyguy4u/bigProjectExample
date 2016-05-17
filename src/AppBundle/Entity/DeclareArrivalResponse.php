<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareArrival;

/**
 * Class DeclareArrivalResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareArrivalResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareArrivalResponse extends DeclareBaseResponse {

  /**
   * @var DeclareArrival
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareArrival", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareArrival")
   */
  private $declareArrivalRequestMessage;

    /**
     * Set declareArrivalRequestMessage
     *
     * @param \AppBundle\Entity\DeclareArrival $declareArrivalRequestMessage
     *
     * @return DeclareArrivalResponse
     */
    public function setDeclareArrivalRequestMessage(\AppBundle\Entity\DeclareArrival $declareArrivalRequestMessage = null)
    {
        $this->declareArrivalRequestMessage = $declareArrivalRequestMessage;

        return $this;
    }

    /**
     * Get declareArrivalRequestMessage
     *
     * @return \AppBundle\Entity\DeclareArrival
     */
    public function getDeclareArrivalRequestMessage()
    {
        return $this->declareArrivalRequestMessage;
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
     * @return DeclareArrivalResponse
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
