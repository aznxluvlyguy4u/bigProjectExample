<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class DeclareLoss
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareLossRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareLoss extends DeclareBase implements DeclareAnimalDataInterface, BasicRvoDeclareInterface
{
    use EntityClassInfo;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="deaths", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ulnNumber;

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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $dateOfDeath;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $reasonOfLoss;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 10)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $ubnDestructor;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="losses", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity="DeclareLossResponse", mappedBy="declareLossRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_loss_request_message_id", referencedColumnName="id")
     * @ORM\OrderBy({"logDate" = "ASC"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\DeclareLossResponse>")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $responses;

    /**
     * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="loss", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     * @Expose
     */
    private $revoke;

    /**
     * DeclareLoss constructor.
     */
    public function __construct() {
        parent::__construct();

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareLossResponse $response
     *
     * @return DeclareLoss
     */
    public function addResponse(\AppBundle\Entity\DeclareLossResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareLossResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareLossResponse $response)
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
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareLoss
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        if($animal != null) {

            if($animal->getUlnCountryCode()!=null && $animal->getUlnNumber()!=null) {
                $this->ulnCountryCode = $animal->getUlnCountryCode();
                $this->ulnNumber = $animal->getUlnNumber();
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
     * Set location
     *
     * @param Location $location
     *
     * @return DeclareLoss
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
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareLoss
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
     * Set dateOfDeath
     *
     * @param \DateTime $dateOfDeath
     *
     * @return DeclareLoss
     */
    public function setDateOfDeath($dateOfDeath)
    {
        $this->dateOfDeath = $dateOfDeath;

        return $this;
    }

    /**
     * Get dateOfDeath
     *
     * @return \DateTime
     */
    public function getDateOfDeath()
    {
        return $this->dateOfDeath;
    }

    /**
     * @return string
     */
    public function getReasonOfLoss()
    {
        return $this->reasonOfLoss;
    }

    /**
     * @param string $reasonOfLoss
     */
    public function setReasonOfLoss($reasonOfLoss)
    {
        $this->reasonOfLoss = $reasonOfLoss;
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
    public function getUbnDestructor()
    {
        return $this->ubnDestructor;
    }

    /**
     * @param string $ubnDestructor
     */
    public function setUbnDestructor($ubnDestructor)
    {
        $this->ubnDestructor = $ubnDestructor;
    }



}
