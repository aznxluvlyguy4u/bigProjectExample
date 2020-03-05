<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class AnimalAnnotation
 * @ORM\Table(name="animal_annotation",indexes={
 *     @ORM\Index(name="animal_annotation_idx", columns={"animal_id", "company_id"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalAnnotationRepository")
 * @package AppBundle\Entity
 */
class AnimalAnnotation
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
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     * @Assert\NotBlank
     */
    private $updatedAt;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="annotations")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Assert\NotBlank
     */
    private $animal;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="animalAnnotations")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Location")
     * @Assert\NotBlank
     */
    private $location;

    /**
     * @var Company
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="animalAnnotations")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Company")
     * @Assert\NotBlank
     */
    private $company;

    /**
     * @var Person
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="animalAnnotations")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Person")
     * @Assert\NotBlank
     */
    private $actionBy;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false, options={"default":""})
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     */
    private $body;

    /**
     * AnimalAnnotation constructor.
     */
    public function __construct() {
        $this->refreshUpdatedAt();
        $this->body = '';
    }


    /**
     * @return AnimalAnnotation
     */
    public function refreshUpdatedAt(): AnimalAnnotation {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("animal_id")
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     * @return int
     */
    public function getAnimalId(): int
    {
        return $this->animal->getId();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("uln")
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     * @return string
     */
    public function getUln(): string
    {
        return $this->animal->getUln();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("action_by_full_name")
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     * @return string
     */
    public function getActionByFullName(): string
    {
        return $this->actionBy->getFullName();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("company_name")
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->company->getCompanyName();
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("ubn")
     * @JMS\Groups({
     *     "ANIMAL_ANNOTATIONS"
     * })
     * @return string
     */
    public function getUbn(): string
    {
        return $this->location->getUbn();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return AnimalAnnotation
     */
    public function setId(int $id): AnimalAnnotation
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param  \DateTime  $updatedAt
     * @return AnimalAnnotation
     */
    public function setUpdatedAt(\DateTime $updatedAt): AnimalAnnotation
    {
        $this->updatedAt = $updatedAt;
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
     * @param  Animal  $animal
     * @return AnimalAnnotation
     */
    public function setAnimal(Animal $animal): AnimalAnnotation
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
     * @param  Location  $location
     * @return AnimalAnnotation
     */
    public function setLocation(Location $location): AnimalAnnotation
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @param  Company  $company
     * @return AnimalAnnotation
     */
    public function setCompany(Company $company): AnimalAnnotation
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return Person
     */
    public function getActionBy(): Person
    {
        return $this->actionBy;
    }

    /**
     * @param  Person  $actionBy
     * @return AnimalAnnotation
     */
    public function setActionBy(Person $actionBy): AnimalAnnotation
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param  string  $body
     * @return AnimalAnnotation
     */
    public function setBody(string $body): AnimalAnnotation
    {
        $this->body = $body;
        return $this;
    }


}
