<?php

namespace AppBundle\JsonFormat;

use AppBundle\JsonFormat\DeclareBirthJsonFormatChild;
use AppBundle\JsonFormat\DeclareBirthJsonFormatEwe;
use AppBundle\JsonFormat\DeclareBirthJsonFormatRam;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @var DeclareBirthJsonFormatRam
     */
    private $father;

    /**
     * @var DeclareBirthJsonFormatEwe
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

        $this->father = new DeclareBirthJsonFormatRam();
        $this->mother = new DeclareBirthJsonFormatEwe();
        //Create responses array
        $this->children = new ArrayCollection();
    }

    /**
     * Add child
     *
     * @param DeclareBirthJsonFormatChild $child
     *
     * @return DeclareBirthJsonFormat
     */
    public function addChild($child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param DeclareBirthJsonFormatChild $child
     */
    public function removeChild($child)
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
     * Set father
     *
     * @param DeclareBirthJsonFormatRam $father
     *
     * @return DeclareBirthJsonFormat
     */
    public function setFather($father = null)
    {
        $this->father = $father;
        return $this;
    }

    /**
     * Get father
     *
     * @return DeclareBirthJsonFormatRam
     */
    public function getFather()
    {
        return $this->father;
    }

    /**
     * Set mother
     *
     * @param DeclareBirthJsonFormatEwe $mother
     *
     * @return DeclareBirthJsonFormat
     */
    public function setMother($mother = null)
    {
        $this->mother = $mother;
        return $this;
    }

    /**
     * Get mother
     *
     * @return DeclareBirthJsonFormatEwe
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
     * @return DeclareBirthJsonFormat
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
