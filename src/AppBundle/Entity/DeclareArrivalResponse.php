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
//JColumn(name="declare_arrival_request_message_id", referencedColumnName="id")

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return DeclareArrivalResponse
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
