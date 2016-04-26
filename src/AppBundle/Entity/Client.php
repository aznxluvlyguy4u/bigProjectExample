<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Client
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ClientRepository")
 * @package AppBundle\Entity
 */
class Client extends Person
{

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $relationNumberKeeper;

  /**
   * @var string
   *
   * @Assert\NotBlank
   * @ORM\Column(type="string")
   * @JMS\Type("string")
   */
  private $objectType;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="Company", mappedBy="owner", cascade={"persist"})
   * @JMS\Type("array")
   */
  private $companies;

  /**
   * Constructor
   */
  public function __construct()
  {
    //Call super constructor first
    parent::__construct();

    $this->objectType = "Client";
    $this->companies = new ArrayCollection();
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
   * Add company
   *
   * @param \AppBundle\Entity\Company $company
   *
   * @return Client
   */
  public function addCompany(\AppBundle\Entity\Company $company)
  {
    $this->companies[] = $company;

    return $this;
  }

  /**
   * Remove company
   *
   * @param \AppBundle\Entity\Company $company
   */
  public function removeCompany(\AppBundle\Entity\Company $company)
  {
    $this->companies->removeElement($company);
  }

  /**
   * Get companies
   *
   * @return \Doctrine\Common\Collections\Collection
   */
  public function getCompanies()
  {
    return $this->companies;
  }

  /**
   * Set username
   *
   * @param string $username
   *
   * @return Client
   */
  public function setUsername($username)
  {
    $this->username = $username;

    return $this;
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

  /**
   * Set objectType
   *
   * @param string $objectType
   *
   * @return Client
   */
  public function setObjectType($objectType)
  {
    $this->objectType = $objectType;

    return $this;
  }

  /**
   * Get objectType
   *
   * @return string
   */
  public function getObjectType()
  {
    return $this->objectType;
  }
}
