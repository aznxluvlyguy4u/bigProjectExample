<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Ram
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RamRepository")
 * @package AppBundle\Entity
 *
 */
class Ram extends Animal
{
    use EntityClassInfo;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentFather")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Animal>")
     */
    private $children;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * 
     */
     private $objectType;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Litter", mappedBy="animalFather")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Litter>")
     * @ORM\OrderBy({"litterDate" = "ASC"})
     */
    private $litters;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Mate>")
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="studRam")
     */
    private $matings;

    /**
     * Ram constructor.
     */
     public function __construct() {
         //Call super constructor first
         parent::__construct();

         $this->objectType = "Ram";
         $this->setAnimalType(AnimalType::sheep);
         $this->setAnimalCategory(3);
         $this->setGender(GenderType::MALE);

         $this->litters = new ArrayCollection();
         $this->children = new ArrayCollection();
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Ram
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
     * @return Ram
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
     * @return ArrayCollection
     */
    public function getMatings()
    {
        return $this->matings;
    }

    /**
     * @param ArrayCollection $matings
     */
    public function setMatings($matings)
    {
        $this->matings = $matings;
    }

    /**
     * Add litter
     *
     * @param \AppBundle\Entity\Litter $litter
     *
     * @return Ram
     */
    public function addLitter(\AppBundle\Entity\Litter $litter)
    {
        $this->litters[] = $litter;

        return $this;
    }

    /**
     * Remove litter
     *
     * @param \AppBundle\Entity\Litter $litter
     */
    public function removeLitter(\AppBundle\Entity\Litter $litter)
    {
        $this->litters->removeElement($litter);
    }

    /**
     * Get litters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLitters()
    {
        return $this->litters;
    }


    public static function getClassName() {
        return get_called_class();
    }


    /**
     * @return ArrayCollection
     */
    public function getEvents()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->orX(
                Criteria::expr()->eq('requestState', RequestStateType::FINISHED),
                Criteria::expr()->eq('requestState', RequestStateType::FINISHED_WITH_WARNING)
            ))
            ->orderBy(array("logDate" => Criteria::DESC))
        ;

        $declareBirths = [];

        /** @var Litter $litter */
        foreach ($this->litters as $litter) {//dump($litter->getChildren());die;
            foreach ($litter->getDeclareBirths() as $birth) {
                $declareBirths[] = $birth;
            }
        }

        return (new ArrayCollection(
            array_merge(
                parent::getEvents()->toArray(),
                $this->matings->toArray(),
                $declareBirths //TODO check if births are properly included
            )
        ))->matching($criteria);
    }
}
