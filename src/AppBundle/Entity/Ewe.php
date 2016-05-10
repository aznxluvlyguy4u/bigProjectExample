<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Enumerator\AnimalType;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Ewe
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EweRepository")
 * @package AppBundle\Entity
 */
class Ewe extends Animal
{
    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentMother")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
     protected $children;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="surrogateMother")
     * @JMS\Type("AppBundle\Entity\Ewe")
     */
    protected $surrogateChildren;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
     protected $objectType;

    /**
     * Ewe constructor.
     */
     public function __construct() {
         //Call super constructor first
         parent::__construct();

         $this->objectType = "Ewe";
         $this->setAnimalType(AnimalType::sheep);
         $this->setGender(AnimalType::FEMALE);

         $this->children = new ArrayCollection();
     }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Ewe
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get objectType
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Animal $child
     *
     * @return Ewe
     */
    public function addChild(\AppBundle\Entity\Animal $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Animal $child
     */
    public function removeChild(\AppBundle\Entity\Animal $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set isAlive
     *
     * @param boolean $isAlive
     *
     * @return Ewe
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;

        return $this;
    }

    /**
     * Get isAlive
     *
     * @return boolean
     */
    public function getIsAlive()
    {
        return $this->isAlive;
    }

    /**
     * Set birth
     *
     * @param \AppBundle\Entity\DeclareBirth $birth
     *
     * @return Ewe
     */
    public function setBirth(\AppBundle\Entity\DeclareBirth $birth = null)
    {
        $this->birth = $birth;

        return $this;
    }

    /**
     * Get birth
     *
     * @return \AppBundle\Entity\DeclareBirth
     */
    public function getBirth()
    {
        return $this->birth;
    }

    /**
     * Set ulnNumber
     *
     * @param string $ulnNumber
     *
     * @return Ewe
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;

        return $this;
    }

    /**
     * Set ulnCountryCode
     *
     * @param string $ulnCountryCode
     *
     * @return Ewe
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;

        return $this;
    }

    /**
     * Set animalOrderNumber
     *
     * @param string $animalOrderNumber
     *
     * @return Ewe
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;

        return $this;
    }

    /**
     * Remove departure
     *
     * @param \AppBundle\Entity\DeclareDepart $departure
     */
    public function removeDeparture(\AppBundle\Entity\DeclareDepart $departure)
    {
        $this->departures->removeElement($departure);
    }

    /**
     * Add surrogateChild
     *
     * @param \AppBundle\Entity\Animal $surrogateChild
     *
     * @return Ewe
     */
    public function addSurrogateChild(\AppBundle\Entity\Animal $surrogateChild)
    {
        $this->surrogateChildren[] = $surrogateChild;

        return $this;
    }

    /**
     * Remove surrogateChild
     *
     * @param \AppBundle\Entity\Animal $surrogateChild
     */
    public function removeSurrogateChild(\AppBundle\Entity\Animal $surrogateChild)
    {
        $this->surrogateChildren->removeElement($surrogateChild);
    }

    /**
     * Get surrogateChildren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSurrogateChildren()
    {
        return $this->surrogateChildren;
    }

    /**
     * Set surrogateMother
     *
     * @param \AppBundle\Entity\Ewe $surrogateMother
     *
     * @return Ewe
     */
    public function setSurrogateMother(\AppBundle\Entity\Ewe $surrogateMother = null)
    {
        $this->surrogateMother = $surrogateMother;

        return $this;
    }

    /**
     * Get surrogateMother
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getSurrogateMother()
    {
        return $this->surrogateMother;
    }
}
