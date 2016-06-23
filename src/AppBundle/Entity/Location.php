<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;
use AppBundle\Entity\DeclareArrival;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Location
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Location
{
  /**
   * @var integer
   *
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   * @Expose
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @Assert\Length(max = 12)
   * @JMS\Type("string")
   * @Expose
   */
  protected $ubn;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   * @Expose
   */
  private $locationHolder;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="DeclareArrival", mappedBy="location")
   */
  protected $arrivals;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="DeclareBirth", mappedBy="location")
   */
  protected $births;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="DeclareDepart", mappedBy="location")
   */
  protected $departures;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="Animal", mappedBy="location")
   * @JMS\Type("AppBundle\Entity\Animal")
   */
  protected $animals;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="DeclareImport", mappedBy="location")
   */
  protected $imports;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="DeclareExport", mappedBy="location")
   */
  protected $exports;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="DeclareTagsTransfer", mappedBy="location")
   */
  protected $tagTransfers;

  /**
   * @var ArrayCollection
   * 
   * @ORM\OneToMany(targetEntity="DeclareLoss", mappedBy="location")
   */
  protected $losses;

  /**
   * @var ArrayCollection
   *
   * @JMS\Type("AppBundle\Entity\DeclareAnimalFlag")
   * @ORM\OneToMany(targetEntity="DeclareAnimalFlag", mappedBy="location", cascade={"persist"})
   */
  protected $flags;

  /**
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="Company", inversedBy="locations", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Company")
   */
  protected $company;

  /**
   * @var LocationAddress
   *
   * @ORM\OneToOne(targetEntity="LocationAddress", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\LocationAddress")
   */
  private $address;

  /**
   * @var ArrayCollection
   *
   * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
   * @ORM\OneToMany(targetEntity="RevokeDeclaration", mappedBy="location", cascade={"persist"})
   */
  protected $revokes;

  /**
   * @ORM\OneToOne(targetEntity="LocationHealth", inversedBy="location")
   * @JMS\Type("AppBundle\Entity\LocationHealth")
   */
  private $locationHealth;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="LocationHealthMessage", mappedBy="location")
   * @ORM\JoinColumn(name="health_message_id", referencedColumnName="id", nullable=true)
   * @JMS\Type("AppBundle\Entity\LocationHealthMessage")
   */
  private $healthMessages;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="AnimalResidence", mappedBy="location")
   * @JMS\Type("AppBundle\Entity\AnimalResidence")
   */
  private $animalResidenceHistory;

  /*
  * Constructor
  */
  public function __construct()
  {
    $this->arrivals = new ArrayCollection();
    $this->births = new ArrayCollection();
    $this->departures = new ArrayCollection();
    $this->imports = new ArrayCollection();
    $this->exports = new ArrayCollection();
    $this->losses = new ArrayCollection();
    $this->animals = new ArrayCollection();
    $this->tagTransfers = new ArrayCollection();
    $this->flags = new ArrayCollection();
    $this->revokes = new ArrayCollection();
    $this->healthMessages = new ArrayCollection();
    $this->animalResidenceHistory = new ArrayCollection();
  }

  /**
   * Add arrival
   *
   * @param \AppBundle\Entity\DeclareArrival $arrival
   *
   * @return Location
   */
  public function addArrival(\AppBundle\Entity\DeclareArrival $arrival)
  {
    $this->arrivals[] = $arrival;

    return $this;
  }

  /**
   * Remove arrival
   *
   * @param \AppBundle\Entity\DeclareArrival $arrival
   */
  public function removeArrival(\AppBundle\Entity\DeclareArrival $arrival)
  {
    $this->arrivals->removeElement($arrival);
  }

  /**
   * Get arrivals
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getArrivals()
  {
    return $this->arrivals;
  }

  /**
   * Add birth
   *
   * @param \AppBundle\Entity\DeclareBirth $birth
   *
   * @return Location
   */
  public function addBirth(\AppBundle\Entity\DeclareBirth $birth)
  {
    $this->births[] = $birth;

    return $this;
  }

  /**
   * Remove birth
   *
   * @param \AppBundle\Entity\DeclareBirth $birth
   */
  public function removeBirth(\AppBundle\Entity\DeclareBirth $birth)
  {
    $this->births->removeElement($birth);
  }

  /**
   * Get births
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getBirths()
  {
    return $this->births;
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
   * Set ubn
   *
   * @param string $ubn
   *
   * @return Location
   */
  public function setUbn($ubn)
  {
    $this->ubn = $ubn;

    return $this;
  }

  /**
   * Get ubn
   *
   * @return string
   */
  public function getUbn()
  {
    return $this->ubn;
  }

  /**
   * Set company
   *
   * @param \AppBundle\Entity\Company $company
   *
   * @return Location
   */
  public function setCompany(\AppBundle\Entity\Company $company = null)
  {
    $this->company = $company;

    return $this;
  }

  /**
   * Get company
   *
   * @return \AppBundle\Entity\Company
   */
  public function getCompany()
  {
    return $this->company;
  }

  /**
   * Set address
   *
   * @param \AppBundle\Entity\LocationAddress $address
   *
   * @return Location
   */
  public function setAddress(\AppBundle\Entity\LocationAddress $address = null)
  {
    $this->address = $address;

    return $this;
  }

  /**
   * Get address
   *
   * @return \AppBundle\Entity\LocationAddress
   */
  public function getAddress()
  {
    return $this->address;
  }

    /**
     * Add import
     *
     * @param \AppBundle\Entity\DeclareImport $import
     *
     * @return Location
     */
    public function addImport(\AppBundle\Entity\DeclareImport $import)
    {
        $this->imports[] = $import;

        return $this;
    }

    /**
     * Remove import
     *
     * @param \AppBundle\Entity\DeclareImport $import
     */
    public function removeImport(\AppBundle\Entity\DeclareImport $import)
    {
        $this->imports->removeElement($import);
    }

    /**
     * Get imports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * Add departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     *
     * @return Location
     */
    public function addDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures[] = $departure;

        return $this;
    }

    /**
     * Remove departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures->removeElement($departure);
    }

    /**
     * Get departures
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDepartures()
    {
        return $this->departures;
    }

    /**
     * Add loss
     *
     * @param \AppBundle\Entity\DeclareLoss $loss
     *
     * @return Location
     */
    public function addLoss(\AppBundle\Entity\DeclareLoss $loss)
    {
      $this->losses[] = $loss;
  
      return $this;
    }
  
    /**
     * Remove loss
     *
     * @param \AppBundle\Entity\DeclareLoss $loss
     */
    public function removeLoss(\AppBundle\Entity\DeclareLoss $loss)
    {
      $this->losses->removeElement($loss);
    }
  
    /**
     * Get losses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLosses()
    {
      return $this->losses;
    }

    /**
     * Add animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return Location
     */
    public function addAnimal(\AppBundle\Entity\Animal $animal)
    {
        $animal->setLocation($this);
        $this->animals[] = $animal;

        return $this;
    }

    /**
     * Remove animal
     *
     * @param \AppBundle\Entity\Animal $animal
     */
    public function removeAnimal(\AppBundle\Entity\Animal $animal)
    {
        $this->animals->removeElement($animal);
    }

    /**
     * Get animals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnimals()
    {
        return $this->animals;
    }

    /**
     * Add export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     *
     * @return Location
     */
    public function addExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports[] = $export;

        return $this;
    }

    /**
     * Remove export
     *
     * @param \AppBundle\Entity\DeclareExport $export
     */
    public function removeExport(\AppBundle\Entity\DeclareExport $export)
    {
        $this->exports->removeElement($export);
    }

    /**
     * Get exports
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExports()
    {
        return $this->exports;
    }


    /**
     * Add tagTransfer
     *
     * @param \AppBundle\Entity\DeclareTagsTransfer $tagTransfer
     *
     * @return Location
     */
    public function addTagTransfer(\AppBundle\Entity\DeclareTagsTransfer $tagTransfer)
    {
        $this->tagTransfers[] = $tagTransfer;

        return $this;
    }

    /**
     * Remove tagTransfer
     *
     * @param \AppBundle\Entity\DeclareTagsTransfer $tagTransfer
     */
    public function removeTagTransfer(\AppBundle\Entity\DeclareTagsTransfer $tagTransfer)
    {
        $this->tagTransfers->removeElement($tagTransfer);
    }

    /**
     * Get tagTransfers
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTagTransfers()
    {
        return $this->tagTransfers;
    }

    /**
     * Add flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     *
     * @return Location
     */
    public function addFlag(\AppBundle\Entity\DeclareAnimalFlag $flag)
    {
        $this->flags[] = $flag;

        return $this;
    }

    /**
     * Remove flag
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $flag
     */
    public function removeFlag(\AppBundle\Entity\DeclareAnimalFlag $flag)
    {
        $this->flags->removeElement($flag);
    }

    /**
     * Get flags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Add revoke
     *
     * @param \AppBundle\Entity\RevokeDeclaration $revoke
     *
     * @return Location
     */
    public function addRevoke(\AppBundle\Entity\RevokeDeclaration $revoke)
    {
        $this->revokes[] = $revoke;

        return $this;
    }

    /**
     * Remove revoke
     *
     * @param \AppBundle\Entity\RevokeDeclaration $revoke
     */
    public function removeRevoke(\AppBundle\Entity\RevokeDeclaration $revoke)
    {
        $this->revokes->removeElement($revoke);
    }

    /**
     * Get revokes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRevokes()
    {
        return $this->revokes;
    }

    /**
     * Set locationHolder
     *
     * @param string $locationHolder
     *
     * @return Location
     */
    public function setLocationHolder($locationHolder)
    {
        $this->locationHolder = $locationHolder;

        return $this;
    }

    /**
     * Get locationHolder
     *
     * @return string
     */
    public function getLocationHolder()
    {
        return $this->locationHolder;
    }

    /**
     * Add healthMessage
     *
     * @param \AppBundle\Entity\LocationHealthMessage $healthMessage
     *
     * @return Location
     */
    public function addHealthMessage(\AppBundle\Entity\LocationHealthMessage $healthMessage)
    {
        $this->healthMessages[] = $healthMessage;

        return $this;
    }

    /**
     * Remove healthMessage
     *
     * @param \AppBundle\Entity\LocationHealthMessage $healthMessage
     */
    public function removeHealthMessage(\AppBundle\Entity\LocationHealthMessage $healthMessage)
    {
        $this->healthMessages->removeElement($healthMessage);
    }

    /**
     * Get healthMessages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHealthMessages()
    {
        return $this->healthMessages;
    }

    /**
     * Set locationHealth
     *
     * @param \AppBundle\Entity\LocationHealth $locationHealth
     *
     * @return Location
     */
    public function setLocationHealth(\AppBundle\Entity\LocationHealth $locationHealth = null)
    {
        $this->locationHealth = $locationHealth;

        return $this;
    }

    /**
     * Get locationHealth
     *
     * @return \AppBundle\Entity\LocationHealth
     */
    public function getLocationHealth() {
      return $this->locationHealth;
    }

    /**
     * Add animalResidenceHistory
     *
     * @param \AppBundle\Entity\AnimalResidence $animalResidenceHistory
     *
     * @return Location
     */
    public function addAnimalResidenceHistory(\AppBundle\Entity\AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory[] = $animalResidenceHistory;

        return $this;
    }

    /**
     * Remove animalResidenceHistory
     *
     * @param \AppBundle\Entity\AnimalResidence $animalResidenceHistory
     */
    public function removeAnimalResidenceHistory(\AppBundle\Entity\AnimalResidence $animalResidenceHistory)
    {
        $this->animalResidenceHistory->removeElement($animalResidenceHistory);
    }

    /**
     * Get animalResidenceHistory
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnimalResidenceHistory()
    {
        return $this->animalResidenceHistory;
    }
}
