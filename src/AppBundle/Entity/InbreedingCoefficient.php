<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class InbreedingCoefficient
 * @ORM\Table(name="inbreeding_coefficient",indexes={
 *     @ORM\Index(
 *          name="inbreeding_coefficient_idx",
 *          columns={"ram_id", "ewe_id", "pair_id"}
 *     ),
 *     @ORM\Index(
 *          name="inbreeding_coefficient_find_global_matches_idx",
 *          columns={"ram_id", "ewe_id", "pair_id"},
 *          options={"where": "find_global_matches"}
 *     ),
 *     @ORM\Index(
 *          name="inbreeding_coefficient_recalculate_idx",
 *          columns={"ram_id", "ewe_id", "pair_id"},
 *          options={"where": "recalculate"}
 *     )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InbreedingCoefficientRepository")
 * @package AppBundle\Entity
 */
class InbreedingCoefficient
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
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @Assert\NotBlank
     */
    private $pairId;

    /**
     * @var Ram
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="inbreedingCoefficients")
     * @ORM\JoinColumn(name="ram_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Ram")
     * @Assert\NotBlank
     */
    private $ram;

    /**
     * @var Ewe
     * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="inbreedingCoefficients")
     * @ORM\JoinColumn(name="ewe_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Ewe")
     * @Assert\NotBlank
     */
    private $ewe;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false, options={"default":0})
     * @Assert\NotBlank
     */
    private $value;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     * @Assert\NotBlank
     */
    private $recalculate;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     * @Assert\NotBlank
     */
    private $findGlobalMatches;

    /**
     * @var ArrayCollection|Animal[]
     *
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="inbreedingCoefficient", fetch="EXTRA_LAZY")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animals;

    /**
     * @var ArrayCollection|Litter[]
     *
     * @ORM\OneToMany(targetEntity="Litter", mappedBy="inbreedingCoefficient", fetch="EXTRA_LAZY")
     * @JMS\Type("AppBundle\Entity\Litter")
     */
    private $litters;
    
    /**
     * Last dateTime when inbreedingCoefficient was matched with this animal
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    public $updatedAt;

    /**
     * InbreedingCoefficient constructor.
     */
    public function __construct()
    {
        $this->recalculate = false;
        $this->findGlobalMatches = false;
        $this->refreshUpdatedAt();
        $this->animals = new ArrayCollection();
        $this->litters = new ArrayCollection();
    }

    /**
     * @return InbreedingCoefficient
     */
    public function refreshUpdatedAt(): InbreedingCoefficient
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return InbreedingCoefficient
     */
    public function setId(int $id): InbreedingCoefficient
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Ram
     */
    public function getRam(): Ram
    {
        return $this->ram;
    }

    /**
     * @param Ram $ram
     * @param Ewe $ewe
     * @return InbreedingCoefficient
     */
    public function setPair(Ram $ram, Ewe $ewe): InbreedingCoefficient
    {
        $this->ram = $ram;
        $this->ewe = $ewe;

        $this->pairId = self::generatePairId($ram->getId(), $ewe->getId());

        return $this;
    }

    private static function generatePairId(int $ramId, int $eweId): string {
        return $ramId . '-' . $eweId;
    }

    /**
     * @return string
     */
    public function getPairId(): string
    {
        return $this->pairId;
    }

    /**
     * @return Ewe
     */
    public function getEwe(): Ewe
    {
        return $this->ewe;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return InbreedingCoefficient
     */
    public function setValue(float $value): InbreedingCoefficient
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRecalculate(): bool
    {
        return $this->recalculate;
    }

    /**
     * @param bool $recalculate
     * @return InbreedingCoefficient
     */
    public function setRecalculate(bool $recalculate): InbreedingCoefficient
    {
        $this->recalculate = $recalculate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFindGlobalMatches(): bool
    {
        return $this->findGlobalMatches;
    }

    /**
     * @param bool $findGlobalMatches
     * @return InbreedingCoefficient
     */
    public function setFindGlobalMatches(bool $findGlobalMatches): InbreedingCoefficient
    {
        $this->findGlobalMatches = $findGlobalMatches;
        return $this;
    }

    /**
     * @param Animal[]|ArrayCollection $animals
     * @return InbreedingCoefficient
     */
    public function setAnimals($animals): InbreedingCoefficient
    {
        $this->animals = $animals;
        return $this;
    }

    /**
     * @param Animal $animal
     * @return InbreedingCoefficient
     */
    public function addAnimal(Animal $animal): InbreedingCoefficient
    {
        $this->animals->add($animal);
        return $this;
    }

    /**
     * @return Animal[]|ArrayCollection
     */
    public function getAnimals()
    {
        return $this->animals;
    }



    /**
     * @param Litter[]|ArrayCollection $litters
     * @return InbreedingCoefficient
     */
    public function setLitters($litters): InbreedingCoefficient
    {
        $this->litters = $litters;
        return $this;
    }

    /**
     * @param Litter $litter
     * @return InbreedingCoefficient
     */
    public function addLitter(Litter $litter): InbreedingCoefficient
    {
        $this->litters->add($litter);
        return $this;
    }

    /**
     * @return Litter[]|ArrayCollection
     */
    public function getLitters()
    {
        return $this->litters;
    }
    

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }


}