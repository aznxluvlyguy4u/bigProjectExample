<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\TagStateType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Tag
 * @ORM\Table(name="tag",indexes={@ORM\Index(name="tag_idx", columns={"location_id", "tag_status"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TagRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Tag
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     * @Expose
     */
    private $tagStatus;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $tagKind;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $tagTypeCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $tagDescription;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * @Expose
     */
    private $animalOrderNumber;

    /**
     * @var DateTime
     *
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $orderDate;

    /**
     * @var string
     *
     * Country code as defined by ISO 3166-1:
     * {https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2}
     *
     * Example: NL(Netherlands), IE(Ireland), DK(Denmark), SE(Sweden)
     *
     * @ORM\Column(type="string")
     * @Assert\Regex("/([A-Z]{2})\b/")
     * @Assert\Length(max = 2)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     * @Expose
     */
    private $ulnCountryCode;

    /**
     * @var string
     *
     * Example: 000000012345
     *
     * @ORM\Column(type="string")
     * @Assert\Regex("/([0-9]{12})\b/")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     * @Expose
     */
    private $ulnNumber;

    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal", mappedBy="assignedTag", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * @var Client
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="tags", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Client")
     */
    private $owner;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="tags", cascade={"persist"})
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $location;

    /**
     * @var DeclareTagsTransfer
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareTagsTransfer", cascade={"persist"}, inversedBy="tags")
     * @JMS\Type("AppBundle\Entity\DeclareTagsTransfer")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $declareTagsTransferRequestMessage;

    /**
     * Tag constructor.
     */
    public function __construct() {
    }

    /**
     * Set tagStatus
     *
     * @param string $tagStatus
     *
     * @return Tag
     */
    public function setTagStatus($tagStatus)
    {
        $this->tagStatus = $tagStatus;

        return $this;
    }

    /**
     * Get tagStatus
     *
     * @return string
     */
    public function getTagStatus()
    {
        return $this->tagStatus;
    }

    /**
     * Set tagKind
     *
     * @param string $tagKind
     *
     * @return Tag
     */
    public function setTagKind($tagKind)
    {
        $this->tagKind = $tagKind;

        return $this;
    }

    /**
     * Get tagKind
     *
     * @return string
     */
    public function getTagKind()
    {
        return $this->tagKind;
    }

    /**
     * Set tagTypeCode
     *
     * @param string $tagTypeCode
     *
     * @return Tag
     */
    public function setTagTypeCode($tagTypeCode)
    {
        $this->tagTypeCode = $tagTypeCode;

        return $this;
    }

    /**
     * Get tagTypeCode
     *
     * @return string
     */
    public function getTagTypeCode()
    {
        return $this->tagTypeCode;
    }

    /**
     * Set tagDescription
     *
     * @param string $tagDescription
     *
     * @return Tag
     */
    public function setTagDescription($tagDescription)
    {
        $this->tagDescription = $tagDescription;

        return $this;
    }

    /**
     * Get tagDescription
     *
     * @return string
     */
    public function getTagDescription()
    {
        return $this->tagDescription;
    }

    /**
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return Tag
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;

        return $this;
    }

    /**
     * Get animalOrderNumber
     *
     * @return string
     */
    public function getAnimalOrderNumber()
    {
        return $this->animalOrderNumber;
    }

    /**
     * Set orderDate
     *
     * @param \DateTime $orderDate
     *
     * @return Tag
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    /**
     * Get orderDate
     *
     * @return \DateTime
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return Tag
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Get ulnCountryCode
     *
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return Tag
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Get ulnNumber
     *
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }


    /**
     * @return string
     */
    public function getUln()
    {
        return $this->ulnCountryCode . $this->ulnNumber;
    }


    public function removeAnimal()
    {
        $this->animal = null;
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
     * Set declareTagsTransferRequestMessage
     *
     * @param \AppBundle\Entity\DeclareTagsTransfer $declareTagsTransferRequestMessage
     *
     * @return Tag
     */
    public function setDeclareTagsTransferRequestMessage(\AppBundle\Entity\DeclareTagsTransfer $declareTagsTransferRequestMessage = null)
    {
        $this->declareTagsTransferRequestMessage = $declareTagsTransferRequestMessage;

        return $this;
    }

    /**
     * Get declareTagsTransferRequestMessage
     *
     * @return \AppBundle\Entity\DeclareTagsTransfer
     */
    public function getDeclareTagsTransferRequestMessage()
    {
        return $this->declareTagsTransferRequestMessage;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return Tag
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
      $this->animal = $animal;
    }

    /* Set owner
     *
     * @param \AppBundle\Entity\Client $owner
     *
     * @return Tag
     */

    public function setOwner(\AppBundle\Entity\Client $owner = null)
    {
        $this->owner = $owner;

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

    /*
     * Get owner
     *
     * @return \AppBundle\Entity\Client
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return int|null
     */
    public function getOwnerId()
    {
        return $this->owner ? $this->owner->getId() : null;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    public static function getClassName() {
        return get_called_class();
    }


    /**
     * @return Tag
     */
    public function unassignTag(): Tag
    {
        $this->setTagStatus(TagStateType::UNASSIGNED);
        return $this;
    }
}
