<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Person;
use AppBundle\Entity\DeclareArrival;

/**
 * Class Location
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationRepository")
 * @package AppBundle\Entity
 */
class Location
{
  /**
   * @var integer
   *
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @Assert\Length(max = 12)
   * @JMS\Type("string")
   */
  protected $ubn;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="DeclareArrival", mappedBy="location")
   */
  protected $arrivals;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="DeclareBirth", mappedBy="location")
   */
  protected $births;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="DeclareDepart", mappedBy="location")
   */
  protected $departures;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="DeclareImport", mappedBy="location")
   */
  protected $imports;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="DeclareLoss", mappedBy="location")
   */
  protected $losses;

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

  /*
  * Constructor
  */
  public function __construct()
  {
    $this->arrivals = new ArrayCollection();
    $this->births = new ArrayCollection();
    $this->departures = new ArrayCollection();
    $this->imports = new ArrayCollection();
    $this->losses = new ArrayCollection();
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
}
