<?php

namespace AppBundle\Entity;

use AppBundle\Entity\ContactData;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;
use AppBundle\Entity\Person;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Location;

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
    private $debtorNumber;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    */
    private $companyName;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    */
    private $vatNumber;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    */
    private $chamberOfCommerceNumber;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    */
    private $companyRelationNumber;

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
    * @JMS\Type("Client")
    */
    protected $owner;

    /**
    * @var CompanyAddress
    *
    * @Assert\NotBlank
    * @ORM\OneToOne(targetEntity="CompanyAddress", cascade={"persist"})
    * @JMS\Type("AppBundle\Entity\CompanyAddress")
    */
    private $address;

    /**
    * @var BillingAddress
    * @ORM\OneToOne(targetEntity="BillingAddress", cascade={"persist"})
    * @JMS\Type("AppBundle\Entity\BillingAddress")
    */
    private $billingAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $telephoneNumber;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $subscriptionDate;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     */
    private $animalHealthSubscription;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     */
    private $isActive;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Invoice", mappedBy="company")
     * @JMS\Type("array")
     */
    private $invoices;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Client", mappedBy="employer")
     * @JMS\Type("array")
     */
    private $companyUsers;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Pedigree", mappedBy="company")
     * @JMS\Type("array")
     */
    private $pedigrees;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $veterinarianDapNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $veterinarianCompanyName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $veterinarianTelephoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $veterinarianEmailAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $notes;

  /**
   * Company constructor.
   */
  public function __construct()
  {
    $this->locations = new ArrayCollection();
    $this->companyUsers = new ArrayCollection();
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
    public function addLocation(Location $location)
    {
        $this->locations[] = $location;

        return $this;
    }

    /**
     * Remove location
     *
     * @param \AppBundle\Entity\Location $location
     */
    public function removeLocation(Location $location)
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
        $owner->addCompany($this);

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

    /**
     * Set address
     *
     * @param \AppBundle\Entity\CompanyAddress $address
     *
     * @return Company
     */
    public function setAddress(\AppBundle\Entity\CompanyAddress $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\CompanyAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set billingAddress
     *
     * @param \AppBundle\Entity\BillingAddress $billingAddress
     *
     * @return Company
     */
    public function setBillingAddress(\AppBundle\Entity\BillingAddress $billingAddress = null)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * Get billingAddress
     *
     * @return \AppBundle\Entity\BillingAddress
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

    /**
     * @return string
     */
    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param string $vatNumber
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    }

    /**
     * @return string
     */
    public function getChamberOfCommerceNumber()
    {
        return $this->chamberOfCommerceNumber;
    }

    /**
     * @param string $chamberOfCommerceNumber
     */
    public function setChamberOfCommerceNumber($chamberOfCommerceNumber)
    {
        $this->chamberOfCommerceNumber = $chamberOfCommerceNumber;
    }

    /**
     * @return string
     */
    public function getCompanyRelationNumber()
    {
        return $this->companyRelationNumber;
    }

    /**
     * @param string $companyRelationNumber
     */
    public function setCompanyRelationNumber($companyRelationNumber)
    {
        $this->companyRelationNumber = $companyRelationNumber;
    }

    /**
     * @return string
     */
    public function getTelephoneNumber()
    {
        return $this->telephoneNumber;
    }

    /**
     * @param string $telephoneNumber
     */
    public function setTelephoneNumber($telephoneNumber)
    {
        $this->telephoneNumber = $telephoneNumber;
    }

    /**
     * @return string
     */
    public function getVeterinarianDapNumber()
    {
        return $this->veterinarianDapNumber;
    }

    /**
     * @param string $veterinarianDapNumber
     */
    public function setVeterinarianDapNumber($veterinarianDapNumber)
    {
        $this->veterinarianDapNumber = $veterinarianDapNumber;
    }

    /**
     * @return string
     */
    public function getVeterinarianCompanyName()
    {
        return $this->veterinarianCompanyName;
    }

    /**
     * @param string $veterinarianCompanyName
     */
    public function setVeterinarianCompanyName($veterinarianCompanyName)
    {
        $this->veterinarianCompanyName = $veterinarianCompanyName;
    }

    /**
     * @return string
     */
    public function getVeterinarianTelephoneNumber()
    {
        return $this->veterinarianTelephoneNumber;
    }

    /**
     * @param string $veterinarianTelephoneNumber
     */
    public function setVeterinarianTelephoneNumber($veterinarianTelephoneNumber)
    {
        $this->veterinarianTelephoneNumber = $veterinarianTelephoneNumber;
    }

    /**
     * @return string
     */
    public function getVeterinarianEmailAddress()
    {
        return $this->veterinarianEmailAddress;
    }

    /**
     * @param string $veterinarianEmailAddress
     */
    public function setVeterinarianEmailAddress($veterinarianEmailAddress)
    {
        $this->veterinarianEmailAddress = strtolower($veterinarianEmailAddress);
    }



    /**
     * @return string
     */
    public function getDebtorNumber()
    {
        return $this->debtorNumber;
    }

    /**
     * @param string $debtorNumber
     */
    public function setDebtorNumber($debtorNumber)
    {
        $this->debtorNumber = $debtorNumber;
    }

    /**
     * @return DateTime
     */
    public function getSubscriptionDate()
    {
        return $this->subscriptionDate;
    }

    /**
     * @param DateTime $subscriptionDate
     */
    public function setSubscriptionDate($subscriptionDate)
    {
        $this->subscriptionDate = $subscriptionDate;
    }

    /**
     * @return mixed
     */
    public function getAnimalHealthSubscription()
    {
        return $this->animalHealthSubscription;
    }

    /**
     * @param mixed $animalHealthSubscription
     */
    public function setAnimalHealthSubscription($animalHealthSubscription)
    {
        $this->animalHealthSubscription = $animalHealthSubscription;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return ArrayCollection
     */
    public function getInvoices()
    {
        return $this->invoices;
    }

    /**
     * @param ArrayCollection $invoices
     */
    public function setInvoices($invoices)
    {
        $this->invoices = $invoices;
    }

    /**
     * @return ArrayCollection
     */
    public function getCompanyUsers()
    {
        return $this->companyUsers;
    }

    /**
     * Add companyUser
     *
     * @param Client $user
     *
     * @return Company
     */
    public function addCompanyUser(Client $user)
    {
        $this->companyUsers[] = $user;

        return $this;
    }

    /**
     * Remove companyUser
     *
     * @param Client $user
     */
    public function removeCompanyUser(Client $user)
    {
        $this->companyUsers->removeElement($user);
    }


    /**
     * @return ArrayCollection
     */
    public function getPedigrees()
    {
        return $this->pedigrees;
    }

    /**
     * @param ArrayCollection $pedigrees
     */
    public function setPedigrees($pedigrees)
    {
        $this->pedigrees = $pedigrees;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }
}
