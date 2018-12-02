<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AnimalRemoval
 * @ORM\Table(name="animal_removal",indexes={
 *     @ORM\Index(name="animal_removal_idx", columns={"previous_location_id", "animal_id"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalRemovalRepository")
 * @package AppBundle\Entity
 */
class AnimalRemoval
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
    private $removalDate;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="animalRemovals")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Assert\NotBlank
     */
    private $animal;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animalRemovals")
     * @ORM\JoinColumn(name="previous_location_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Location")
     * @Assert\NotBlank
     */
    private $previousLocation;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @Assert\NotBlank
     */
    private $removedByRvoLeadingSync;

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
        $this->removedByRvoLeadingSync = false;
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
     * @return AnimalRemoval
     */
    public function setLogDate(\DateTime $logDate): AnimalRemoval
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRemovalDate(): \DateTime
    {
        return $this->removalDate;
    }

    /**
     * @param \DateTime $removalDate
     * @return AnimalRemoval
     */
    public function setRemovalDate(\DateTime $removalDate): AnimalRemoval
    {
        $this->removalDate = $removalDate;
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
     * @return AnimalRemoval
     */
    public function setAnimal(Animal $animal): AnimalRemoval
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return Location
     */
    public function getPreviousLocation(): Location
    {
        return $this->previousLocation;
    }

    /**
     * @param Location $previousLocation
     * @return AnimalRemoval
     */
    public function setPreviousLocation(Location $previousLocation): AnimalRemoval
    {
        $this->previousLocation = $previousLocation;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRemovedByRvoLeadingSync(): bool
    {
        return $this->removedByRvoLeadingSync;
    }

    /**
     * @param bool $removedByRvoLeadingSync
     * @return AnimalRemoval
     */
    public function setRemovedByRvoLeadingSync(bool $removedByRvoLeadingSync): AnimalRemoval
    {
        $this->removedByRvoLeadingSync = $removedByRvoLeadingSync;
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
     * @return AnimalRemoval
     */
    public function setRetrieveAnimals(?RetrieveAnimals $retrieveAnimals): AnimalRemoval
    {
        $this->retrieveAnimals = $retrieveAnimals;
        return $this;
    }


}