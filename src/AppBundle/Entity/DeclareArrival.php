<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareArrival
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareArrivalRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareArrival extends DeclareBase implements RelocationDeclareInterface
{
    use EntityClassInfo;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="arrivals")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $pedigreeCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $pedigreeNumber;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $animalType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @Expose
     */
    private $animalObjectType;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $arrivalDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ubnPreviousOwner;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="arrivals", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     * @Expose
     *
     */
    private $location;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $isImportAnimal;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isArrivedFromOtherNsfoClient;

    /**
     * @ORM\OneToMany(targetEntity="DeclareArrivalResponse", mappedBy="declareArrivalRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_arrival_request_message_id", referencedColumnName="id")
     * @ORM\OrderBy({"logDate" = "ASC"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\DeclareArrivalResponse>")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $responses;

    /**
     * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="arrival", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     * @Expose
     */
    private $revoke;

    /**
     * @ORM\OneToOne(targetEntity="LocationHealthMessage", inversedBy="arrival")
     * @JMS\Type("AppBundle\Entity\LocationHealthMessage")
     */
    private $healthMessage;

    /**
     * @ORM\ManyToOne(targetEntity="LocationHealthQueue", inversedBy="arrivals")
     * @JMS\Type("AppBundle\Entity\LocationHealthQueue")
     */
    private $locationHealthQueue;

    /**
     * @var DepartArrivalTransaction|null
     * @ORM\OneToOne(targetEntity="DepartArrivalTransaction",
     *     inversedBy="arrival", cascade={"persist","refresh"})
     * @ORM\JoinColumn(name="transaction_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\DepartArrivalTransaction")
     */
    private $transaction;

    /**
     * DeclareArrival constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->setRequestState(RequestStateType::OPEN);
        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Set arrivalDate
     *
     * @param \DateTime $arrivalDate
     *
     * @return DeclareArrival
     */
    public function setArrivalDate($arrivalDate)
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    /**
     * Get arrivalDate
     *
     * @return \DateTime
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * Set ubnPreviousOwner
     *
     * @param string $ubnPreviousOwner
     *
     * @return DeclareArrival
     */
    public function setUbnPreviousOwner($ubnPreviousOwner)
    {
        $this->ubnPreviousOwner = StringUtil::removeLeadingZeroes($ubnPreviousOwner);

        return $this;
    }

    /**
     * Get ubnPreviousOwner
     *
     * @return string
     */
    public function getUbnPreviousOwner()
    {
        return $this->ubnPreviousOwner;
    }


    /**
     * Set importAnimal
     *
     * @param boolean $isImportAnimal
     *
     * @return DeclareArrival
     */
    public function setIsImportAnimal($isImportAnimal)
    {
        $this->isImportAnimal = $isImportAnimal;

        return $this;
    }

    /**
     * Get importAnimal
     *
     * @return boolean
     */
    public function getIsImportAnimal()
    {
        return $this->isImportAnimal;
    }

    /**
     * @return boolean
     */
    public function getIsArrivedFromOtherNsfoClient()
    {
        return $this->isArrivedFromOtherNsfoClient;
    }

    /**
     * @param boolean $isArrivedFromOtherNsfoClient
     */
    public function setIsArrivedFromOtherNsfoClient($isArrivedFromOtherNsfoClient)
    {
        $this->isArrivedFromOtherNsfoClient = $isArrivedFromOtherNsfoClient;
    }

    /**
     * Set location
     *
     * @param Location $location
     *
     * @return DeclareArrival
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($location ? $location->getUbn() : null);

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     *
     * @return DeclareArrival
     */
    public function addResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
    {
        $this->responses->add($response);

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareArrivalResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareArrivalResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
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
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareArrival
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        if($animal != null) {

            if($animal->getUlnCountryCode()!=null && $animal->getUlnNumber()!=null) {
                $this->ulnCountryCode = $animal->getUlnCountryCode();
                $this->ulnNumber = $animal->getUlnNumber();
            }

            if ($animal->getPedigreeCountryCode()!=null && $animal->getPedigreeNumber()!=null){
                $this->pedigreeCountryCode = $animal->getPedigreeCountryCode();
                $this->pedigreeNumber = $animal->getPedigreeNumber();
            }

            $this->setAnimalObjectType(Utils::getClassName($animal));
        }

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Get Animal.id
     *
     * @return int
     */
    public function getAnimalId(): ?int
    {
        return $this->animal ? $this->animal->getId() : null;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareArrival
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
     * @return RevokeDeclaration
     */
    public function getRevoke()
    {
        return $this->revoke;
    }

    /**
     * @param RevokeDeclaration $revoke
     */
    public function setRevoke(RevokeDeclaration $revoke = null)
    {
        $this->revoke = $revoke;
    }

    /**
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * @param string $ulnCountryCode
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = StringUtil::trimAndStringToUpperIfNotNull($ulnCountryCode);
    }

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = StringUtil::trimIfNotNull($ulnNumber);
    }

    /**
     * @return string
     */
    public function getPedigreeCountryCode()
    {
        return $this->pedigreeCountryCode;
    }

    /**
     * @param string $pedigreeCountryCode
     */
    public function setPedigreeCountryCode($pedigreeCountryCode)
    {
        $this->pedigreeCountryCode = StringUtil::trimAndStringToUpperIfNotNull($pedigreeCountryCode);
    }

    /**
     * @return string
     */
    public function getPedigreeNumber()
    {
        return $this->pedigreeNumber;
    }

    /**
     * @param string $pedigreeNumber
     */
    public function setPedigreeNumber($pedigreeNumber)
    {
        $this->pedigreeNumber = StringUtil::trimIfNotNull($pedigreeNumber);
    }

    /**
     * @return int
     */
    public function getAnimalType()
    {
        return $this->animalType;
    }

    /**
     * @param int $animalType
     */
    public function setAnimalType($animalType)
    {
        $this->animalType = $animalType;
    }

    /**
     * @return string
     */
    public function getAnimalObjectType()
    {
        return $this->animalObjectType;
    }

    /**
     * @param string $animalObjectType
     */
    public function setAnimalObjectType($animalObjectType)
    {
        $this->animalObjectType = $animalObjectType;
    }

    /**
     * Set healthMessage
     *
     * @param \AppBundle\Entity\LocationHealthMessage $healthMessage
     *
     * @return DeclareArrival
     */
    public function setHealthMessage(\AppBundle\Entity\LocationHealthMessage $healthMessage = null)
    {
        $this->healthMessage = $healthMessage;

        return $this;
    }

    /**
     * Get healthMessage
     *
     * @return \AppBundle\Entity\LocationHealthMessage
     */
    public function getHealthMessage()
    {
        return $this->healthMessage;
    }


    /**
     * @return LocationHealthQueue
     */
    public function getLocationHealthQueue()
    {
        return $this->locationHealthQueue;
    }

    /**
     * @param LocationHealthQueue $locationHealthQueue
     */
    public function setLocationHealthQueue($locationHealthQueue)
    {
        $this->locationHealthQueue = $locationHealthQueue;
    }

    /**
     * @return DepartArrivalTransaction|null
     */
    public function getTransaction(): ?DepartArrivalTransaction
    {
        return $this->transaction;
    }

    /**
     * @param DepartArrivalTransaction|null $transaction
     * @return DeclareArrival
     */
    public function setTransaction(?DepartArrivalTransaction $transaction): DeclareArrival
    {
        $this->transaction = $transaction;
        return $this;
    }


}
