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
use AppBundle\Entity\DeclareImportResponse;

/**
 * Class DeclareImport
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareImportRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareImport extends DeclareBase
{

  /**
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="Animal", inversedBy="imports", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Animal")
   * @Expose
   */
  private $animal;

  /**
   * 2016-04-01T22:00:48.131Z
   *
   * @ORM\Column(type="datetime")
   * @Assert\Date
   * @Assert\NotBlank
   * @JMS\Type("DateTime")
   * @Expose
   */
  private $importDate;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 10)
   * @JMS\Type("string")
   * @Expose
   */
  private $ubnPreviousOwner;

  /**
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="Location", inversedBy="imports", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Location")
   */
  private $location;

  /**
   * @ORM\Column(type="boolean")
   * @JMS\Type("boolean")
   * @Expose
   */
  private $importAnimal;

  /**
   * @ORM\OneToMany(targetEntity="DeclareImportResponse", mappedBy="declareImportRequestMessage", cascade={"persist"})
   * @ORM\JoinColumn(name="declare_import_request_message_id", referencedColumnName="id")
   * @JMS\Type("array")
   * @Expose
   */
  private $responses;

  /**
   * DeclareArrival constructor.
   */
  public function __construct() {
    parent::__construct();

    $this->setRequestState('open');

    //Create responses array
    $this->responses = new ArrayCollection();
    $this->importAnimal = true;
  }


    /**
     * Set importDate
     *
     * @param \DateTime $importDate
     *
     * @return DeclareImport
     */
    public function setImportDate($importDate)
    {
        $this->importDate = $importDate;

        return $this;
    }

    /**
     * Get importDate
     *
     * @return \DateTime
     */
    public function getImportDate()
    {
        return $this->importDate;
    }

    /**
     * Set ubnPreviousOwner
     *
     * @param string $ubnPreviousOwner
     *
     * @return DeclareImport
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = $ubnPreviousOwner;

        return $this;
    }

    /**
     * Get ubnPreviousOwner
     *
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }

    /**
     * Set importAnimal
     *
     * @param boolean $importAnimal
     *
     * @return DeclareImport
     */
    public function setImportAnimal($importAnimal)
    {
        $this->importAnimal = $importAnimal;

        return $this;
    }

    /**
     * Get importAnimal
     *
     * @return boolean
     */
    public function getImportAnimal()
    {
        return $this->importAnimal;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareImport
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareImport
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($location->getUbn());

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     *
     * @return DeclareImport
     */
    public function addResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
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
