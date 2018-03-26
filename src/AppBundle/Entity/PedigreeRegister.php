<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class PedigreeRegister
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PedigreeRegisterRepository")
 * @package AppBundle\Entity
 */
class PedigreeRegister
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "USER_MEASUREMENT"
     * })
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT"
     * })
     */
    private $abbreviation;

    /**
     * @var PedigreeCode
     * @ORM\ManyToOne(targetEntity="PedigreeCode", cascade={"persist"})
     * @ORM\JoinColumn(name="pedigree_code_id", referencedColumnName="id")
     */

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="PedigreeCode")
     * @ORM\JoinTable(name="pedigree_register_pedigree_codes",
     *      joinColumns={@ORM\JoinColumn(name="pedigree_register_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="pedigree_code_id", referencedColumnName="id")}
     * )
     * @JMS\Type("ArrayCollection<AppBundle\Entity\PedigreeCode>")
     */
    private $pedigreeCodes;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "ANIMALS_BATCH_EDIT",
     *     "USER_MEASUREMENT"
     * })
     */
    private $fullName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $endDate;
    
    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="created_by_id", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $creationDate;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="updated_by_id", referencedColumnName="id")
     */
    private $updatedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $updateDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $specie;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "USER_MEASUREMENT"
     * })
     */
    private $isRegisteredWithNsfo;

    /**
     * PedigreeRegister constructor.
     * @param string $abbreviation
     * @param string $fullName
     * @param boolean $isRegisteredWithNsfo
     * @param int $id
     */
    public function __construct($abbreviation = null, $fullName = null, $isRegisteredWithNsfo = true, $id = null)
    {
        $this->id = $id; //Used for JMS Serializer
        $this->abbreviation = $abbreviation;
        $this->fullName = $fullName;
        $this->isRegisteredWithNsfo = $isRegisteredWithNsfo;
        $this->initializePedigreeCodes();
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAbbreviation()
    {
        return $this->abbreviation;
    }

    /**
     * @param string $abbreviation
     */
    public function setAbbreviation($abbreviation)
    {
        $this->abbreviation = $abbreviation;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return Person
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param Person $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return Person
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @param Person $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * @param \DateTime $updateDate
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;
    }

    /**
     * @return string
     */
    public function getSpecie()
    {
        return $this->specie;
    }

    /**
     * @param string $specie
     */
    public function setSpecie($specie)
    {
        $this->specie = $specie;
    }

    /**
     * @return ArrayCollection
     */
    public function getPedigreeCodes()
    {
        $this->initializePedigreeCodes();
        return $this->pedigreeCodes;
    }

    private function initializePedigreeCodes()
    {
        if ($this->pedigreeCodes === null) {
            $this->pedigreeCodes = new ArrayCollection();
        }
    }

    /**
     * @param ArrayCollection $pedigreeCodes
     * @return PedigreeRegister
     */
    public function setPedigreeCodes($pedigreeCodes)
    {
        $this->pedigreeCodes = $pedigreeCodes;
        return $this;
    }

    /**
     * @param PedigreeCode $pedigreeCode
     * @return ArrayCollection
     */
    public function addPedigreeCode($pedigreeCode)
    {
        $this->getPedigreeCodes()->add($pedigreeCode);
        return $this->pedigreeCodes;
    }

    /**
     * @param PedigreeCode $pedigreeCode
     * @return ArrayCollection
     */
    public function removePedigreeCode($pedigreeCode)
    {
        $this->getPedigreeCodes()->removeElement($pedigreeCode);
        return $this->pedigreeCodes;
    }

    /**
     * @return boolean
     */
    public function getIsRegisteredWithNsfo()
    {
        return $this->isRegisteredWithNsfo;
    }

    /**
     * @param boolean $isRegisteredWithNsfo
     */
    public function setIsRegisteredWithNsfo($isRegisteredWithNsfo)
    {
        $this->isRegisteredWithNsfo = $isRegisteredWithNsfo;
    }



}