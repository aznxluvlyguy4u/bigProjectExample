<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\StringUtil;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

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
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "INVOICE_NO_COMPANY",
     * })
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "INVOICE",
     *     "DOSSIER"
     * })
     */
    private $companyId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     *  @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "UBN",
     *     "DOSSIER"
     * })
    */
    private $debtorNumber;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "UBN",
     *     "DOSSIER"
     * })
    */
    private $companyName;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
    */
    private $vatNumber;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
    */
    private $chamberOfCommerceNumber;

    /**
    * @var string
    *
    * @ORM\Column(type="string", nullable=true)
    * @JMS\Type("string")
    * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
    */
    private $companyRelationNumber;

    /**
    * @var ArrayCollection|Location[]
    *
    * @ORM\OneToMany(targetEntity="Location", mappedBy="company", cascade={"persist"}, fetch="EAGER")
    * @JMS\Type("AppBundle\Entity\Location")
    * @JMS\Groups({
    *     "INVOICE",
    *     "INVOICE_NO_COMPANY",
    *     "UBN",
    *     "DOSSIER"
    * })
    */
    private $locations;

    /**
    * @var Client
    *
    * @Assert\NotBlank
    * @ORM\ManyToOne(targetEntity="Client", inversedBy="companies", cascade={"persist"})
    * @JMS\Type("AppBundle\Entity\Client")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "GHOST_LOGIN",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
    */
    protected $owner;

    /**
    * @var CompanyAddress
    *
    * @Assert\NotBlank
    * @ORM\OneToOne(targetEntity="CompanyAddress", cascade={"persist"})
    * @JMS\Type("AppBundle\Entity\CompanyAddress")
    * @JMS\Groups({
    *     "UBN",
    *     "INVOICE",
    *     "INVOICE_NO_COMPANY",
    *     "DOSSIER"
    * })
    */
    private $address;

    /**
    * @var BillingAddress
    * @ORM\OneToOne(targetEntity="BillingAddress", cascade={"persist"})
    * @JMS\Type("AppBundle\Entity\BillingAddress")
    * @JMS\Groups({
    *     "INVOICE",
    *     "DOSSIER"
    * })
    */
    private $billingAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "DOSSIER"
     *     })
     */
    private $telephoneNumber;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     */
    private $subscriptionDate;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     */
    private $animalHealthSubscription;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "DOSSIER"
     * })
     */
    private $isActive;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Invoice", mappedBy="company")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Invoice>")
     *
     */
    private $invoices;

    /**
     * @var ArrayCollection|Client[]
     *
     * @ORM\OneToMany(targetEntity="Client", mappedBy="employer", cascade={"persist"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Person>")
     * @JMS\Groups({
     *     "GHOST_LOGIN",
     *     "DOSSIER"
     * })
     */
    private $companyUsers;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     *
     */
    private $veterinarianDapNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     */
    private $veterinarianCompanyName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     */
    private $veterinarianTelephoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     */
    private $veterinarianEmailAddress;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CompanyNote", mappedBy="company")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\CompanyNote>")
     * @JMS\Groups({
     *     "DOSSIER"
     *     })
     */
    private $notes;


    /**
     * @var boolean
     *
     * @Assert\NotNull
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS"
     * })
     */
    private $isRevealHistoricAnimals;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $lastMakeLivestockPublicDate;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\SerializedName("twinfield_administration_code")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
     */
    private $twinfieldOfficeCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $debtorNumberYear;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $debtorNumberCompanyType;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $debtorNumberOrdinal;

    /**
     * @var ResultTableAnimalCounts|null
     * @ORM\OneToOne(targetEntity="ResultTableAnimalCounts", mappedBy="company", cascade={"persist", "remove"})
     * @JMS\Type("AppBundle\Entity\ResultTableAnimalCounts")
     */
    private $resultTableAnimalCounts;

    /**
     * @var ArrayCollection|AnimalAnnotation[]
     * @ORM\OrderBy({"updatedAt" = "DESC"})
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\AnimalAnnotation", mappedBy="company", cascade={"persist", "remove"}, fetch="LAZY")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\AnimalAnnotation>")
     */
    private $animalAnnotations;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     * @JMS\Type("boolean")
     * @Assert\NotBlank(message="auto.debit.not_blank")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
     */
    private $hasAutoDebit;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Groups({
     *     "DOSSIER"
     * })
     */
    private $createdAt;

  /**
   * Company constructor.
   */
  public function __construct()
  {
    $this->locations = new ArrayCollection();
    $this->invoices = new ArrayCollection();
    $this->companyUsers = new ArrayCollection();
    $this->setCompanyId(Utils::generateTokenCode());
    $this->notes = new ArrayCollection();
    $this->invoices = new ArrayCollection();
    $this->animalAnnotations = new ArrayCollection();
    $this->lastMakeLivestockPublicDate = new DateTime();
    $this->hasAutoDebit = false;
    $this->createdAt = new DateTime();
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
        $this->companyName = StringUtil::trimIfNotNull($companyName);

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
     * @return \Doctrine\Common\Collections\Collection|Location[]
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * @param bool $onlyReturnActiveUbns
     * @return array|string[]
     */
    public function getLocationIds(bool $onlyReturnActiveUbns = true): array
    {
        $ubns = [];
        foreach ($this->locations as $location) {
            if (!$onlyReturnActiveUbns || $location->getIsActive()) {
                $ubns[] = $location->getUbn();
            }
        }
        return $ubns;
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
     * @param  Client  $client
     * @return bool
     */
    public function isCompanyUserOrOwner(Client $client): bool
    {
        if ($this->getOwner() && $this->getOwner()->getId() === $client->getId()) {
            return true;
        }

        foreach ($this->companyUsers as $companyUser) {
            if ($companyUser->getId() === $client->getId()) {
                return true;
            }
        }

        return false;
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
        $this->vatNumber = StringUtil::trimIfNotNull($vatNumber);
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
        $this->chamberOfCommerceNumber = StringUtil::trimIfNotNull($chamberOfCommerceNumber);
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
        $this->companyRelationNumber = StringUtil::trimIfNotNull($companyRelationNumber);
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
     * @return Company
     */
    public function setTelephoneNumber($telephoneNumber)
    {
        $this->telephoneNumber = StringUtil::trimIfNotNull($telephoneNumber);

        return $this;
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
        $this->veterinarianDapNumber = StringUtil::trimIfNotNull($veterinarianDapNumber);
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
        $this->veterinarianCompanyName = StringUtil::trimIfNotNull($veterinarianCompanyName);
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
        $this->veterinarianTelephoneNumber = StringUtil::trimIfNotNull($veterinarianTelephoneNumber);
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
        $this->veterinarianEmailAddress = StringUtil::trimIfNotNull(strtolower($veterinarianEmailAddress));
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
     * @return Company
     */
    public function setDebtorNumber($debtorNumber)
    {
        $this->debtorNumber = StringUtil::trimIfNotNull($debtorNumber);

        return $this;
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
     * @return Company
     */
    public function setAnimalHealthSubscription($animalHealthSubscription)
    {
        $this->animalHealthSubscription = $animalHealthSubscription;

        return $this;
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
     * @return Company
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
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
     * @return Company
     */
    public function setIsRevealHistoricAnimals($isRevealHistoricAnimals)
    {
        $this->isRevealHistoricAnimals = $isRevealHistoricAnimals;
        $this->resetLastMakeLivestockPublicDate();

        return $this;
    }

    public function addInvoice(Invoice $invoice){
        $this->invoices[] = $invoice;
    }

    public function removeInvoice(Invoice $invoice){
        $this->invoices->removeElement($invoice);
    }

    /**
     * @return DateTime|null
     */
    public function getLastMakeLivestockPublicDate(): ?DateTime
    {
        return $this->lastMakeLivestockPublicDate;
    }

    /**
     * @return Company
     */
    public function resetLastMakeLivestockPublicDate(): Company
    {
        $this->lastMakeLivestockPublicDate = new DateTime();
        return $this;
    }

    /**
     * @param DateTime $lastMakeLivestockPublicDate
     * @return Company
     */
    public function setLastMakeLivestockPublicDate(DateTime $lastMakeLivestockPublicDate): Company
    {
        $this->lastMakeLivestockPublicDate = $lastMakeLivestockPublicDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTwinfieldOfficeCode(): ?string
    {
        return $this->twinfieldOfficeCode;
    }

    /**
     * @param string|null $twinfieldOfficeCode
     */
    public function setTwinfieldOfficeCode(?string $twinfieldOfficeCode)
    {
        $this->twinfieldOfficeCode = $twinfieldOfficeCode;
    }

    /**
     * @return string
     */
    public function getDebtorNumberYear(): string
    {
        return $this->debtorNumberYear;
    }

    /**
     * @param string $debtorNumberYear
     * @return Company
     */
    public function setDebtorNumberYear(string $debtorNumberYear): self
    {
        $this->debtorNumberYear = $debtorNumberYear;

        return $this;
    }

    /**
     * @return string
     */
    public function getDebtorNumberCompanyType(): string
    {
        return $this->debtorNumberCompanyType;
    }

    /**
     * @param string $debtorNumberCompanyType
     * @return Company
     */
    public function setDebtorNumberCompanyType(string $debtorNumberCompanyType): self
    {
        $this->debtorNumberCompanyType = $debtorNumberCompanyType;

        return $this;
    }

    /**
     * @return integer
     */
    public function getDebtorNumberOrdinal()
    {
        return $this->debtorNumberOrdinal;
    }

    /**
     * @param integer $debtorNumberOrdinal
     * @return Company
     */
    public function setDebtorNumberOrdinal($debtorNumberOrdinal): self
    {
        $this->debtorNumberOrdinal = $debtorNumberOrdinal;

        return $this;
    }

    /**
     * @return string
     */
    public function getDebtorCode(): string
    {
        return $this->debtorNumberYear."-".$this->debtorNumberCompanyType."-".$this->debtorNumberOrdinal;
    }

    /**
     * @param bool $onlyReturnActiveUbns
     * @return array|string[]
     */
    public function getUbns(bool $onlyReturnActiveUbns = true): array
    {
        $ubns = [];
        foreach ($this->locations as $location) {
            if (!$onlyReturnActiveUbns || $location->getIsActive()) {
                $ubns[] = $location->getUbn();
            }
        }
        return $ubns;
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


    /**
     * @return int|null
     */
    public function getBillingAddressCountryId(): ?int
    {
        return $this->getBillingAddress() ? $this->getBillingAddress()->getCountryId() : null;
    }


    /**
     * @return int|null
     */
    public function getAddressCountryId(): ?int
    {
        return $this->getAddress() ? $this->getAddress()->getCountryId() : null;
    }

    /**
     * @return ResultTableAnimalCounts|null
     */
    public function getResultTableAnimalCounts(): ?ResultTableAnimalCounts
    {
        return $this->resultTableAnimalCounts;
    }

    /**
     * @param ResultTableAnimalCounts|null $resultTableAnimalCounts
     * @return Company
     */
    public function setResultTableAnimalCounts(?ResultTableAnimalCounts $resultTableAnimalCounts): Company
    {
        $this->resultTableAnimalCounts = $resultTableAnimalCounts;
        return $this;
    }

    /**
     * @return AnimalAnnotation[]|ArrayCollection
     */
    public function getAnimalAnnotations()
    {
        return $this->animalAnnotations;
    }

    /**
     * @param  AnimalAnnotation[]|ArrayCollection  $annotations
     * @return Company
     */
    public function setAnimalAnnotations(ArrayCollection $annotations)
    {
        $this->animalAnnotations = $annotations;
        return $this;
    }

    /**
     * Add annotation
     *
     * @param AnimalAnnotation $annotation
     *
     * @return Company
     */
    public function addAnimalAnnotation(AnimalAnnotation $annotation)
    {
        $this->animalAnnotations->add($annotation);
        return $this;
    }

    /**
     * Remove annotation
     *
     * @param AnimalAnnotation $annotation
     */
    public function removeAnimalAnnotation(AnimalAnnotation $annotation)
    {
        $this->animalAnnotations->removeElement($annotation);
    }

    /**
     * @return bool
     */
    public function isHasAutoDebit(): bool
    {
        return $this->hasAutoDebit;
    }

    /**
     * @param bool $hasAutoDebit
     * @return Company
     */
    public function setHasAutoDebit(bool $hasAutoDebit): Company
    {
        $this->hasAutoDebit = $hasAutoDebit;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return Company
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
