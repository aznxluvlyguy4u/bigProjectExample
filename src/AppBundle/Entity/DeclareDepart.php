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
 * Class DeclareDepart
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareDepartRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareDepart extends DeclareBase implements RelocationDeclareInterface
{
    use EntityClassInfo;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="departures", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
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
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $isExportAnimal;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isDepartedAnimal;

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
    private $departDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 20)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $reasonOfDepart;


    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 10)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ubnNewOwner;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="departures", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity="DeclareDepartResponse", mappedBy="declareDepartRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_depart_request_message_id", referencedColumnName="id")
     * @ORM\OrderBy({"logDate" = "ASC"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\DeclareDepartResponse>")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     */
    private $responses;

    /**
     * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="depart", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     * @Expose
     */
    private $revoke;

    /**
     * @var DepartArrivalTransaction|null
     * @ORM\OneToOne(targetEntity="DepartArrivalTransaction",
     *     inversedBy="depart", cascade={"persist","refresh"})
     * @ORM\JoinColumn(name="transaction_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\DepartArrivalTransaction")
     */
    private $transaction;

    /**
     * DeclareDepart constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->setRequestState(RequestStateType::OPEN);

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareDepartResponse $response
     *
     * @return DeclareDepart
     */
    public function addResponse(\AppBundle\Entity\DeclareDepartResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareDepartResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareDepartResponse $response)
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
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareDepart
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
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareDepart
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
     * @return \DateTime
     */
    public function getDepartDate()
    {
        return $this->departDate;
    }

    /**
     * @param \DateTime $departDate
     *
     * @return DeclareDepart
     */
    public function setDepartDate($departDate)
    {
        $this->departDate = $departDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getUbnNewOwner()
    {
        return $this->ubnNewOwner;
    }

    /**
     * @param string $ubnNewOwner
     *
     * @return DeclareDepart
     */
    public function setUbnNewOwner($ubnNewOwner)
    {
        $this->ubnNewOwner = StringUtil::removeLeadingZeroes($ubnNewOwner);

        return $this;
    }

    /**
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     *
     * @return \AppBundle\Entity\DeclareDepart
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;
        $this->setUbn($location ? $location->getUbn() : null);

        return $this;
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
        $this->ulnCountryCode = $ulnCountryCode;
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
        $this->ulnNumber = $ulnNumber;
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
        $this->pedigreeCountryCode = $pedigreeCountryCode;
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
        $this->pedigreeNumber = $pedigreeNumber;
    }

    /**
     * @return boolean
     */
    public function getIsExportAnimal()
    {
        return $this->isExportAnimal;
    }

    /**
     * @param boolean $isExportAnimal
     */
    public function setIsExportAnimal($isExportAnimal)
    {
        $this->isExportAnimal = $isExportAnimal;
    }

    /**
     * @return boolean
     */
    public function getIsDepartedAnimal()
    {
        return $this->isDepartedAnimal;
    }

    /**
     * @param boolean $isDepartedAnimal
     */
    public function setIsDepartedAnimal($isDepartedAnimal)
    {
        $this->isDepartedAnimal = $isDepartedAnimal;
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
     * @return string
     */
    public function getReasonOfDepart()
    {
        return $this->reasonOfDepart;
    }

    /**
     * @param string $reasonOfDepart
     */
    public function setReasonOfDepart($reasonOfDepart)
    {
        $this->reasonOfDepart = $reasonOfDepart;
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
     * @return DeclareDepart
     */
    public function setTransaction(?DepartArrivalTransaction $transaction): DeclareDepart
    {
        $this->transaction = $transaction;
        return $this;
    }

}
