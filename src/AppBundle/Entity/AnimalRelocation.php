<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AnimalRemoval
 * @ORM\Table(name="animal_relocation",indexes={
 *     @ORM\Index(name="animal_relocation_idx", columns={"location_id", "animal_id"})
 * })
 * @ORM\Entity(repositoryClass="AnimalRelocationRepository")
 * @package AppBundle\Entity
 */
class AnimalRelocation
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @Assert\NotBlank
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @Assert\NotBlank
     */
    private $relocationDate;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="animalRelocations")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Assert\NotBlank
     */
    private $animal;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animalRelocations")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Location")
     * @Assert\NotBlank
     */
    private $location;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $isRemoval;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $relocatedByRvoLeadingSync;

    /**
     * @var RetrieveAnimals|null
     *
     * @ORM\ManyToOne(targetEntity="RetrieveAnimals", inversedBy="animalRemovals")
     * @ORM\JoinColumn(name="retrieve_animals_id", referencedColumnName="id", onDelete="set null")
     */
    private $retrieveAnimals;

    /**
     * AnimalRemoval constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
        $this->relocatedByRvoLeadingSync = false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate(): \DateTime
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     * @return AnimalRelocation
     */
    public function setLogDate(\DateTime $logDate): AnimalRelocation
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRelocationDate(): \DateTime
    {
        return $this->relocationDate;
    }

    /**
     * @param \DateTime $relocationDate
     * @return AnimalRelocation
     */
    public function setRelocationDate(\DateTime $relocationDate): AnimalRelocation
    {
        $this->relocationDate = $relocationDate;
        return $this;
    }

    /**
     * @return Animal
     */
    public function getAnimal(): Animal
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     * @return AnimalRelocation
     */
    public function setAnimal(Animal $animal): AnimalRelocation
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @param Location $location
     * @return AnimalRelocation
     */
    public function setLocation(Location $location): AnimalRelocation
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRemoval(): bool
    {
        return $this->isRemoval;
    }

    /**
     * @param bool $isRemoval
     * @return AnimalRelocation
     */
    public function setIsRemoval(bool $isRemoval): AnimalRelocation
    {
        $this->isRemoval = $isRemoval;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRelocatedByRvoLeadingSync(): bool
    {
        return $this->relocatedByRvoLeadingSync;
    }

    /**
     * @param bool $relocatedByRvoLeadingSync
     * @return AnimalRelocation
     */
    public function setRelocatedByRvoLeadingSync(bool $relocatedByRvoLeadingSync): AnimalRelocation
    {
        $this->relocatedByRvoLeadingSync = $relocatedByRvoLeadingSync;
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
     * @param RetrieveAnimals|null $retrieveAnimals
     * @return AnimalRelocation
     */
    public function setRetrieveAnimals(?RetrieveAnimals $retrieveAnimals): AnimalRelocation
    {
        $this->retrieveAnimals = $retrieveAnimals;
        return $this;
    }


}