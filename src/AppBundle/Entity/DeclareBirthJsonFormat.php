<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Animal;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class DeclareBirthCreateJsonFormat
 * @package AppBundle\Entity
 */
class DeclareBirthJsonFormat
{
    /**
     * @var string
     */
    private $isAborted;

    /**
     * @var string
     */
    private $isPseudoPregnancy;


    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var DateTime
     */
    private $dateOfBirth;

    /**
     * @var string
     */
    private $birthType;

    /**
     * @var integer
     */
    private $litterSize;

    /**
     * @var integer
     */
    private $aliveCount;

    /**
     * @var Ram
     */
    private $father;

    /**
     * @var Ewe
     */
    private $mother;

    /**
     * @var ArrayCollection
     */
    private $children;


    /**
     * Constructor.
     */
    public function __construct() {

        //Create responses array
        $this->children = new ArrayCollection();
    }

    /**
     * Add child
     *
     * @param Ram|Ewe $child
     *
     * @return DeclareBirth
     */
    public function addChild($child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param Ram|Ewe $child
     */
    public function removeChild($child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get child
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }


    /**
     * Set father
     *
     * @param \AppBundle\Entity\Ram $father
     *
     * @return DeclareBirth
     */
    public function setFather(\AppBundle\Entity\Ram $father = null)
    {
        $this->father = $father;
        return $this;
    }

    /**
     * Get father
     *
     * @return \AppBundle\Entity\Ram
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * Set mother
     *
     * @param \AppBundle\Entity\Ewe $mother
     *
     * @return DeclareBirth
     */
    public function setMother(\AppBundle\Entity\Ewe $mother = null)
    {
        $this->mother = $mother;
        return $this;
    }

    /**
     * Get mother
     *
     * @return \AppBundle\Entity\Ewe
     */
    public function getMother()
    {
        return $this->mother;
    }


    /**
     * Set dateOfBirth
     *
     * @param \DateTime $dateOfBirth
     *
     * @return DeclareBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @return string
     */
    public function getIsAborted()
    {
        return $this->isAborted;
    }

    /**
     * @param string $isAborted
     */
    public function setIsAborted($isAborted)
    {
        $this->isAborted = $isAborted;
    }

    /**
     * @return string
     */
    public function getIsPseudoPregnancy()
    {
        return $this->isPseudoPregnancy;
    }

    /**
     * @param string $isPseudoPregnancy
     */
    public function setIsPseudoPregnancy($isPseudoPregnancy)
    {
        $this->isPseudoPregnancy = $isPseudoPregnancy;
    }


    /**
     * @return string
     */
    public function getBirthType()
    {
        return $this->birthType;
    }

    /**
     * @param string $birthType
     */
    public function setBirthType($birthType)
    {
        $this->birthType = $birthType;
    }

    /**
     * @return integer
     */
    public function getLitterSize()
    {
        return $this->litterSize;
    }

    /**
     * @param integer $litterSize
     */
    public function setLitterSize($litterSize)
    {
        $this->litterSize = $litterSize;
    }

    /**
     * @return int
     */
    public function getAliveCount()
    {
        return $this->aliveCount;
    }

    /**
     * @param int $aliveCount
     */
    public function setAliveCount($aliveCount)
    {
        $this->aliveCount = $aliveCount;
    }
}
