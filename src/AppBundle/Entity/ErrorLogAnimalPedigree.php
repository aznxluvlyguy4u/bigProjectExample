<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ErrorLogAnimalPedigree
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ErrorLogAnimalPedigreeRepository")
 * @package AppBundle\Entity
 */
class ErrorLogAnimalPedigree
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;


    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $parentIds;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $parentTypes;


    public function __construct()
    {
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
     * @return ErrorLogAnimalPedigree
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
     * @return ErrorLogAnimalPedigree
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentIds()
    {
        return $this->parentIds;
    }

    /**
     * @param string $parentIds
     * @return ErrorLogAnimalPedigree
     */
    public function setParentIds($parentIds)
    {
        $this->parentIds = $parentIds;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentTypes()
    {
        return $this->parentTypes;
    }

    /**
     * @param string $parentTypes
     * @return ErrorLogAnimalPedigree
     */
    public function setParentTypes($parentTypes)
    {
        $this->parentTypes = $parentTypes;
        return $this;
    }


    /**
     * @return array
     */
    public function getParentTypesAsArray()
    {
        return json_decode($this->parentTypes);
    }


    /**
     * @return array
     */
    public function getParentIdsAsArray()
    {
        return json_decode($this->parentIds);
    }
}