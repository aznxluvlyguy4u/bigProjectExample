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
     * @JMS\Type("Company")
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
}
