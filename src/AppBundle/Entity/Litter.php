<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Animal;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Litter
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LitterRepository")
 * @package AppBundle\Entity
 */
class Litter {

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $litterDate;

    /**
     * @ORM\ManyToOne(targetEntity="Ram", inversedBy="litters")
     * @ORM\JoinColumn(name="animal_father_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Ram")
     */
    private $animalFather;

    /**
    * @ORM\ManyToOne(targetEntity="Ewe", inversedBy="litters")
    * @ORM\JoinColumn(name="animal_mother_id", referencedColumnName="id")
    * @JMS\Type("AppBundle\Entity\Ewe")
    */
    private $animalMother;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $litterGroup;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $size;

    /**
     * @ORM\OneToMany(targetEntity="Animal", mappedBy="litter")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $children;
    
    /**
     * Litter constructor.
     */
    public function __construct() {
        $this->children = new ArrayCollection();
        $this->logDate = new \DateTime();
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
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return Litter
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set litterDate
     *
     * @param \DateTime $litterDate
     *
     * @return Litter
     */
    public function setLitterDate($litterDate)
    {
        $this->litterDate = $litterDate;

        return $this;
    }

    /**
     * Get litterDate
     *
     * @return \DateTime
     */
    public function getLitterDate()
    {
        return $this->litterDate;
    }

    /**
     * Set size
     *
     * @param integer $size
     *
     * @return Litter
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set animalFather
     *
     * @param \AppBundle\Entity\Animal $animalFather
     *
     * @return Litter
     */
    public function setAnimalFather(\AppBundle\Entity\Animal $animalFather = null)
    {
        $this->animalFather = $animalFather;

        return $this;
    }

    /**
     * Get animalFather
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimalFather()
    {
        return $this->animalFather;
    }

    /**
     * Set animalMother
     *
     * @param \AppBundle\Entity\Animal $animalMother
     *
     * @return Litter
     */
    public function setAnimalMother(\AppBundle\Entity\Animal $animalMother = null)
    {
        $this->animalMother = $animalMother;

        return $this;
    }

    /**
     * Get animalMother
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimalMother()
    {
        return $this->animalMother;
    }

    /**
     * Add child
     *
     * @param Animal $child
     *
     * @return Litter
     */
    public function addChild(Animal $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param Animal $child
     */
    public function removeChild(Animal $child)
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
     * @return string
     */
    public function getLitterGroup()
    {
        return $this->litterGroup;
    }

    /**
     * @param string $litterGroup
     */
    public function setLitterGroup($litterGroup)
    {
        $this->litterGroup = $litterGroup;
    }

    
}
