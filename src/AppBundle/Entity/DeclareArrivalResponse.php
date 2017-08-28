<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareArrival;

/**
 * Class DeclareArrivalResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareArrivalResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareArrivalResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

  /**
   * @var DeclareArrival
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareArrival", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareArrival")
   */
  private $declareArrivalRequestMessage;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $arrivalDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     */
    private $ubnPreviousOwner;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $gender;

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

    /**
     * @return \DateTime
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * @param \DateTime $arrivalDate
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;
    }

    /**
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }

    /**
     * @param string $ubnPreviousOwner
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = $ubnPreviousOwner;
    }




    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return DeclareArrivalResponse
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }
}
