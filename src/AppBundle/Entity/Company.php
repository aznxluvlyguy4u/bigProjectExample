<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use AppBundle\Entity\Person;

/**
 * Class Company
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CompanyRepository")
 * @package AppBundle\Entity
 */
class Company
{
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   */
  private $companyName;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="Location", mappedBy="company", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Location")
   */
  private $locations;

  /**
   * @var Client
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="Client", inversedBy="companies", cascade={"persist"})
   * @JMS\Type("AppBundle\Entity\Client")
   */
  protected $owner;

  /**
   * Company constructor.
   */
  public function __construct()
  {
    $this->locations = new ArrayCollection();
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
     * Set companyName
     *
     * @param string $companyName
     *
     * @return Company
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * Add location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return Company
     */
    public function addLocation(\AppBundle\Entity\Location $location)
    {
        $this->locations[] = $location;

        return $this;
    }

    /**
     * Remove location
     *
     * @param \AppBundle\Entity\Location $location
     */
    public function removeLocation(\AppBundle\Entity\Location $location)
    {
        $this->locations->removeElement($location);
    }

    /**
     * Get locations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Set owner
     *
     * @param \AppBundle\Entity\Client $owner
     *
     * @return Company
     */
    public function setOwner(\AppBundle\Entity\Client $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \AppBundle\Entity\Client
     */
    public function getOwner()
    {
        return $this->owner;
    }
}
