<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

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
   * @JMS\Groups({
   *     "RESPONSE_PERSISTENCE"
   * })
   */
  private $declareArrivalRequestMessage;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $arrivalDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ubnPreviousOwner;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
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


    /**
     * @param DeclareArrival $arrival
     * @return DeclareArrivalResponse
     */
    public function setDeclareArrivalIncludingAllValues(DeclareArrival $arrival): DeclareArrivalResponse
    {
        $this->setDeclareBaseValues($arrival);
        $this->setDeclareArrivalRequestMessage($arrival);
        $this->setArrivalDate($arrival->getArrivalDate());
        $this->setUbnPreviousOwner($arrival->getUbnPreviousOwner());
        if ($arrival->getAnimal()) {
            $this->setGender($arrival->getAnimal()->getGender());
        }
        return $this;
    }
}
