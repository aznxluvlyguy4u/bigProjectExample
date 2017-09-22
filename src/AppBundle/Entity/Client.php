<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Client
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ClientRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Client extends Person
{
    use EntityClassInfo;

    /**
    * @ORM\Column(type="string", nullable=true)
    * @Assert\Length(max = 20)
    * @JMS\Type("string")=
    * @JMS\Groups({
     *     "ACTION_LOG_ADMIN",
     *     "ACTION_LOG_USER",
     *     "INVOICE"
     * })
    * @Expose
    */
    private $relationNumberKeeper;

    /**
    * @var string
    *
    * @Assert\NotBlank
    * @ORM\Column(type="string")
    * @JMS\Type("string")
    *
    */
    private $objectType;

    /**
    * @var ArrayCollection
    *
    * @ORM\OneToMany(targetEntity="Company", mappedBy="owner", cascade={"persist"})
    * @JMS\Type("array")
    *
    */
    private $companies;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="companyUsers", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Company")
     */
    private $employer;

    /**
    * @var ArrayCollection
    *
    * @ORM\OneToMany(targetEntity="Tag", mappedBy="owner", cascade={"persist"})
    * @JMS\Type("array")
    */
    private $tags;

    /**
    * Constructor
    */
    public function __construct($firstName = null, $lastName = null, $emailAddress = null,
                              $password = '', $username = null, $cellphoneNumber = null, $relationNumberKeeper = null)
    {
    //Call super constructor first
    parent::__construct($firstName, $lastName, $emailAddress, $password, $username, $cellphoneNumber);

    $this->setRelationNumberKeeper($relationNumberKeeper);
    $this->objectType = "Client";
    $this->companies = new ArrayCollection();
    $this->tags = new ArrayCollection();
    }

    /**
    * @return ArrayCollection
    */
    public function getTags()
    {
    return $this->tags;
    }

    /**
    * @param ArrayCollection $tags
    */
    public function setTags($tags)
    {
    $this->tags = $tags;
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
    $this->relationNumberKeeper = trim($relationNumberKeeper);

    return $this;
    }

    /**
    * Get relationNumberKeeper
    *
    * @return string
    */
    public function getRelationNumberKeeper()
    {
        if($this->getEmployer() != null) {
            return $this->getEmployer()->getOwner()->getRelationNumberKeeper();
        }

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
        if($this->getEmployer() != null) {
            return $this->getEmployer()->getOwner()->getCompanies();
        }
        return $this->companies;
    }

    /**
    * Set companies
    *
    * @param \Doctrine\Common\Collections\Collection $companies
    */
    public function setCompanies($companies)
    {
    $this->companies = $companies;
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
    $this->username = trim($username);

    return $this;
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

    /**
     * Add tag
     *
     * @param \AppBundle\Entity\Tag $tag
     *
     * @return Client
     */
    public function addTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \AppBundle\Entity\Tag $tag
     */
    public function removeTag(\AppBundle\Entity\Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * @return Company
     */
    public function getEmployer()
    {
        return $this->employer;
    }

    /**
     * @param Company $employer
     */
    public function setEmployer($employer)
    {
        $this->employer = $employer;
    }

    /**
     * @return boolean
     */
    public function hasEmployer()
    {
        if($this->employer instanceof Company) {
            return true;
        } else {
            return false;
        }
    }
}
