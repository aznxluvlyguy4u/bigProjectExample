<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class HealthCheckTask
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\HealthCheckTaskRepository")
 * @package AppBundle\Entity
 */
class HealthCheckTask
{
    use EntityClassInfo;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnCountryCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ulnNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $originUbn;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $destinationUbn;

    /**
     * @var Location
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location")
     * @ORM\JoinColumn(name="destination_location_id", referencedColumnName="id",
     *     nullable = false, onDelete="CASCADE")
     * @JMS\Type("Location")
     */
    private $destinationLocation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $arrivalDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $syncDate;

    /**
     * @var RetrieveAnimals|null
     *
     * @ORM\ManyToOne(targetEntity="RetrieveAnimals", fetch="LAZY")
     * @ORM\JoinColumn(name="retrieve_animals_id", referencedColumnName="id", onDelete="set null")
     */
    private $retrieveAnimals;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $isProcessing;

    public function __construct()
    {
        $this->isProcessing = false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return HealthCheckTask
     */
    public function setType(string $type): HealthCheckTask
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getUlnCountryCode(): string
    {
        return $this->ulnCountryCode;
    }

    /**
     * @param string $ulnCountryCode
     * @return HealthCheckTask
     */
    public function setUlnCountryCode(string $ulnCountryCode): HealthCheckTask
    {
        $this->ulnCountryCode = $ulnCountryCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getUlnNumber(): string
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     * @return HealthCheckTask
     */
    public function setUlnNumber(string $ulnNumber): HealthCheckTask
    {
        $this->ulnNumber = $ulnNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginUbn(): string
    {
        return $this->originUbn;
    }

    /**
     * @param string $originUbn
     * @return HealthCheckTask
     */
    public function setOriginUbn(string $originUbn): HealthCheckTask
    {
        $this->originUbn = $originUbn;
        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationUbn(): string
    {
        return $this->destinationUbn;
    }

    /**
     * @param string $destinationUbn
     * @return HealthCheckTask
     */
    public function setDestinationUbn(string $destinationUbn): HealthCheckTask
    {
        $this->destinationUbn = $destinationUbn;
        return $this;
    }

    /**
     * @return Location
     */
    public function getDestinationLocation(): Location
    {
        return $this->destinationLocation;
    }

    /**
     * @return integer|null
     */
    public function getDestinationLocationId(): ?int
    {
        return $this->destinationLocation ? $this->destinationLocation->getId() : null;
    }

    /**
     * @param Location $destinationLocation
     * @return HealthCheckTask
     */
    public function setDestinationLocation(Location $destinationLocation): HealthCheckTask
    {
        $this->destinationLocation = $destinationLocation;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getArrivalDate(): \DateTime
    {
        return $this->arrivalDate;
    }

    /**
     * @param \DateTime $arrivalDate
     * @return HealthCheckTask
     */
    public function setArrivalDate(\DateTime $arrivalDate): HealthCheckTask
    {
        $this->arrivalDate = $arrivalDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSyncDate(): \DateTime
    {
        return $this->syncDate;
    }

    /**
     * @param \DateTime $syncDate
     * @return HealthCheckTask
     */
    public function setSyncDate(\DateTime $syncDate): HealthCheckTask
    {
        $this->syncDate = $syncDate;
        return $this;
    }

    /**
     * @return RetrieveAnimals|null
     */
    public function getRetrieveAnimals(): ?RetrieveAnimals
    {
        return $this->retrieveAnimals;
    }

    /**
     * @return int|null
     */
    public function getRetrieveAnimalsId(): ?int
    {
        return $this->retrieveAnimals ? $this->retrieveAnimals->getId() : null;
    }

    /**
     * @param RetrieveAnimals|null $retrieveAnimals
     * @return HealthCheckTask
     */
    public function setRetrieveAnimals(?RetrieveAnimals $retrieveAnimals): HealthCheckTask
    {
        $this->retrieveAnimals = $retrieveAnimals;
        return $this;
    }

    /**
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->isProcessing;
    }

    /**
     * @param bool $isProcessing
     * @return HealthCheckTask
     */
    public function setIsProcessing(bool $isProcessing): HealthCheckTask
    {
        $this->isProcessing = $isProcessing;
        return $this;
    }


}