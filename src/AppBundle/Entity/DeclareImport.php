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
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @Expose
   */
  private $animalCountryOrigin;

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
  private $isImportAnimal;

  /**
   * @ORM\OneToMany(targetEntity="DeclareImportResponse", mappedBy="declareImportRequestMessage", cascade={"persist"})
   * @ORM\JoinColumn(name="declare_import_request_message_id", referencedColumnName="id")
   * @JMS\Type("array")
   * @Expose
   */
  private $responses;

  /**
   * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="import", cascade={"persist"})
   * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true)
   * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
   * @Expose
   */
  private $revoke;

  /**
   * DeclareArrival constructor.
   */
  public function __construct() {
    parent::__construct();

    $this->setRequestState('open');

    //Create responses array
    $this->responses = new ArrayCollection();
    $this->isImportAnimal = true;
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
     * Set importAnimal
     *
     * @param boolean $isImportAnimal
     *
     * @return DeclareImport
     */
    public function setIsImportAnimal($isImportAnimal)
    {
        $this->isImportAnimal = $isImportAnimal;

        return $this;
    }

    /**
     * Get importAnimal
     *
     * @return boolean
     */
    public function getIsImportAnimal()
    {
        return $this->isImportAnimal;
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

    /**
     * Set animalCountryOrigin
     *
     * @param string $animalCountryOrigin
     *
     * @return DeclareImport
     */
    public function setAnimalCountryOrigin($animalCountryOrigin)
    {
        $this->animalCountryOrigin = $animalCountryOrigin;

        return $this;
    }

    /**
     * Get animalCountryOrigin
     *
     * @return string
     */
    public function getAnimalCountryOrigin() {
      return $this->animalCountryOrigin;
    }

    /*
     * @return RevokeDeclaration
     */
    public function getRevoke()
    {
        return $this->revoke;
    }

    /**
     * @param RevokeDeclaration $revoke
     */
    public function setRevoke($revoke = null)
    {
        $this->revoke = $revoke;
    }
}
