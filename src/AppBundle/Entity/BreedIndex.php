<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedIndex
 *
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedIndexRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"Exterior" = "ExteriorBreedIndex",
 *                       "Fertility" = "FertilityBreedIndex",
 *                        "LambMeat" = "LambMeatBreedIndex",
 *                  "WormResistance" = "WormResistanceBreedIndex"})
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                        "Exterior" : "AppBundle\Entity\ExteriorBreedIndex",
 *                       "Fertility" : "AppBundle\Entity\FertilityBreedIndex",
 *                        "LambMeat" : "AppBundle\Entity\LambMeatBreedIndex",
 *                  "WormResistance" : "AppBundle\Entity\WormResistanceBreedIndex"},
 *     groups = {
 *     "MIXBLUP"
 * })
 */
abstract class BreedIndex
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $id;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $animal;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $generationDate;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $index;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     * @Assert\NotBlank
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $accuracy;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @JMS\Type("integer")
     * @Assert\NotBlank
     * @JMS\Groups({
     *     "MIXBLUP"
     * })
     */
    private $ranking;

    public function __construct()
    {
        $this->logDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return BreedIndex
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     * @return BreedIndex
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     * @return BreedIndex
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getGenerationDate()
    {
        return $this->generationDate;
    }

    /**
     * @param \DateTime $generationDate
     * @return BreedIndex
     */
    public function setGenerationDate($generationDate)
    {
        $this->generationDate = $generationDate;
        return $this;
    }

    /**
     * @return float
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param float $index
     * @return BreedIndex
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return float
     */
    public function getAccuracy()
    {
        return $this->accuracy;
    }

    /**
     * @param float $accuracy
     * @return BreedIndex
     */
    public function setAccuracy($accuracy)
    {
        $this->accuracy = $accuracy;
        return $this;
    }

    /**
     * @return int
     */
    public function getRanking()
    {
        return $this->ranking;
    }

    /**
     * @param int $ranking
     * @return BreedIndex
     */
    public function setRanking($ranking)
    {
        $this->ranking = $ranking;
        return $this;
    }


    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            "exterior" => "Exterior",
            "fertility" => "Fertility",
            "lamb_meat" => "LambMeat",
            "worm_resistance" => "WormResistance",
        ];
    }

}