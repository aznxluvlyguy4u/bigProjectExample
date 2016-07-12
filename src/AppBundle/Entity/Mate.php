<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Mate
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MateRepository")
 * @package AppBundle\Entity
 */
class Mate {

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $startDate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_father_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $studRam;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Animal")
     * @ORM\JoinTable(name="mate_stud_ewes",
     *      joinColumns={@ORM\JoinColumn(name="mate_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="animal_id", referencedColumnName="id")}
     *      )
     */
    private $studEwes;

    public function __construct() {
      $this->logDate = new \DateTime();
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
     * @return Mate
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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Mate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Mate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set studMale
     *
     * @param \AppBundle\Entity\Animal $studRam
     *
     * @return Mate
     */
    public function setStudRam(\AppBundle\Entity\Animal $studRam = null)
    {
        $this->studRam = $studRam;

        return $this;
    }

    /**
     * Get studMale
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getStudRam()
    {
        return $this->studRam;
    }

    /**
     * Add studEwe
     *
     * @param \AppBundle\Entity\Animal $studEwe
     *
     * @return Mate
     */
    public function addStudEwe(\AppBundle\Entity\Animal $studEwe)
    {
        $this->studEwes[] = $studEwe;

        return $this;
    }

    /**
     * Remove studEwe
     *
     * @param \AppBundle\Entity\Animal $studEwe
     */
    public function removeStudEwe(\AppBundle\Entity\Animal $studEwe)
    {
        $this->studEwes->removeElement($studEwe);
    }

    /**
     * Get studEwes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStudEwes()
    {
        return $this->studEwes;
    }
}
