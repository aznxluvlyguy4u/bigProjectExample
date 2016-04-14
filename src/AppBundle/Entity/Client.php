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
}
