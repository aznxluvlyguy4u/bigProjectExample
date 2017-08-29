<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Traits\EntityClassInfo;
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
    use EntityClassInfo;

    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="IDENTITY")
    */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    private $companyId;

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
    * @JMS\Groups({"ANIMAL_DETAILS"})
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
    * @ORM\OneToMany(targetEntity="Location", mappedBy="company", cascade={"persist"}, fetch="EAGER")
    * @JMS\Type("AppBundle\Entity\Location")
    */
    private $locations;

    /**
    * @var Client
    *
    * @Assert\NotBlank
    * @ORM\ManyToOne(targetEntity="Client", inversedBy="companies", cascade={"persist"})
    * @JMS\Type("AppBundle\Entity\Client")
    * @JMS\Groups({"ANIMAL_DETAILS"})
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
     * @JMS\Groups({"ANIMAL_DETAILS"})
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
     * @ORM\OneToMany(targetEntity="Client", mappedBy="employer", cascade={"persist"})
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CompanyNote", mappedBy="company")
     * @JMS\Type("array")
     */
    private $notes;


    /**
     * @var boolean
     *
     * @Assert\NotBlank
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({"ANIMAL_DETAILS"})
     */
    private $isRevealHistoricAnimals;

  /**
   * Company constructor.
   */
  public function __construct()
  {
    $this->locations = new ArrayCollection();
    $this->companyUsers = new ArrayCollection();
    $this->setCompanyId(Utils::generateTokenCode());
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
     * @param string $companyId
     */
    public function setCompanyId($companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * @return string
     */
    public function getCompanyId()
    {
        return $this->companyId;
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
        $this->companyName = trim($companyName);

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
     * @param ArrayCollection $locations
     */
    public function setLocations($locations)
    {
        $this->locations = $locations;
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
        $this->vatNumber = trim($vatNumber);
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
        $this->chamberOfCommerceNumber = trim($chamberOfCommerceNumber);
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
        $this->companyRelationNumber = trim($companyRelationNumber);
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
        $this->telephoneNumber = trim($telephoneNumber);
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
        $this->veterinarianDapNumber = trim($veterinarianDapNumber);
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
        $this->veterinarianCompanyName = trim($veterinarianCompanyName);
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
        $this->veterinarianTelephoneNumber = trim($veterinarianTelephoneNumber);
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
        $this->veterinarianEmailAddress = trim(strtolower($veterinarianEmailAddress));
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
        $this->debtorNumber = trim($debtorNumber);
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
     * @param ArrayCollection $companyUsers
     */
    public function setCompanyUsers($companyUsers)
    {
        $this->companyUsers = $companyUsers;
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

    /**
     * Add Note
     *
     * @param CompanyNote $note
     *
     * @return Company
     */
    public function addNote(CompanyNote $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove Note
     *
     * @param CompanyNote $note
     */
    public function removeNote(CompanyNote $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * @return boolean
     */
    public function getIsRevealHistoricAnimals()
    {
        return $this->isRevealHistoricAnimals;
    }

    /**
     * @param boolean $isRevealHistoricAnimals
     */
    public function setIsRevealHistoricAnimals($isRevealHistoricAnimals)
    {
        $this->isRevealHistoricAnimals = $isRevealHistoricAnimals;
    }


    /**
     * @param null $nullReplacement
     * @return null|string
     */
    public function getOwnersRelationNumberKeeper($nullReplacement = null)
    {
        $relationNumberKeeper = $nullReplacement;
        if ($this->getOwner()) {
            $relationNumberKeeper = $this->getOwner()->getRelationNumberKeeper();
            if ($relationNumberKeeper === null || trim($relationNumberKeeper) === '') {
                $relationNumberKeeper = $nullReplacement;
            }
        }
        return $relationNumberKeeper;
    }
}
