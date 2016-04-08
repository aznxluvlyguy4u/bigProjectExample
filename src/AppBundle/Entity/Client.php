<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Client
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ClientRepository")
 * @package AppBundle\Entity
 */
class Client extends Person
{

  /**
   * @var Company
   *
   * @ORM\OneToMany(targetEntity="Location", mappedBy="owners",cascade={"persist"})
   */
  private $locations;

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $relationNumberKeeper;

  //private $companies;

  /**
   * Constructor
   */
  public function __construct()
  {
    //Call super constructor first
    parent::__construct();
    $this->locations = new \Doctrine\Common\Collections\ArrayCollection();
  }


  /**
   * Set relationNumberKeeper
   *
   * @param string $relationNumberKeeper
   *
   * @return Client
   */
  public function setRelationNumberKeeper($relationNumberKeeper)
  {
    $this->relationNumberKeeper = $relationNumberKeeper;

    return $this;
  }

  /**
   * Get relationNumberKeeper
   *
   * @return string
   */
  public function getRelationNumberKeeper()
  {
    return $this->relationNumberKeeper;
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
   * Add location
   *
   * @param \AppBundle\Entity\Location $location
   *
   * @return Client
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
     * Set accessToken
     *
     * @param string $accessToken
     *
     * @return Client
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
