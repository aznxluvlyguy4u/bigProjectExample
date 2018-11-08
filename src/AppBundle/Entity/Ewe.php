<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Criteria\MateCriteria;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Ewe
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EweRepository")
 * @package AppBundle\Entity
 */
class Ewe extends Animal implements ParentInterface
{
    use EntityClassInfo;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentMother")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Animal>")
     */
    private $children;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="surrogate")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Animal>")
     */
    protected $surrogateChildren;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * 
     */
     protected $objectType;

      /**
       * @var ArrayCollection
       * 
       * @ORM\OneToMany(targetEntity="Litter", mappedBy="animalMother")
       * @JMS\Type("ArrayCollection<AppBundle\Entity\Litter>")
       * @ORM\OrderBy({"litterDate" = "ASC"})
       */
    private $litters;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Mate>")
     * @ORM\OneToMany(targetEntity="Mate", mappedBy="studEwe")
     * @JMS\Groups({
     *     "MATINGS"
     * })
     */
    private $matings;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("last_mate")
     * @JMS\Groups({
     *     "LAST_MATE"
     * })
     * @return Mate|null
     */
    public function getLastActiveMate()
    {
        if ($this->getMatings()->count() > 0) {
            return $this->getMatings()
                ->matching(MateCriteria::requestStateIsFinished())
                ->matching(MateCriteria::hasNoLitter())
                ->matching(MateCriteria::isOverwrittenVersion(false))
                ->matching(MateCriteria::orderByEndDateDesc())
                ->first();
        }
        return null;
    }

    /**
     * Ewe constructor.
     */
     public function __construct() {
         //Call super constructor first
         parent::__construct();

         $this->objectType = "Ewe";
         $this->setAnimalType(AnimalType::sheep);
         $this->setGender(GenderType::FEMALE);
         $this->setAnimalCategory(Constant::DEFAULT_ANIMAL_CATEGORY);
       
         $this->litters = new ArrayCollection();
         $this->children = new ArrayCollection();
         $this->matings = new ArrayCollection();
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
     * @return \Doctrine\Common\Collections\Collection|Animal[]
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
        // Necessary, because serializer will make empty arrays null
        if ($this->matings === null) {
            $this->matings = new ArrayCollection();
        }

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
     * @param Mate $mate
     */
    public function addMate(Mate $mate)
    {
        if ($mate) {
            $this->getMatings()->add($mate);
        }
    }

    /*
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
     * Add litter
     *
     * @param \AppBundle\Entity\Litter $litter
     *
     * @return Ewe
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
        foreach ($this->litters as $litter) {
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


    public function onlyKeepLastActiveMateInMatings()
    {
        $lastActiveMate = $this->getLastActiveMate();
        $this->setMatings(new ArrayCollection());
        if ($lastActiveMate) {
            $this->addMate($lastActiveMate);
        }
    }
}
