<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedIndexCalculationTerms
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedIndexCalculationTermsRepository")
 * @package AppBundle\Entity
 */
class BreedIndexCalculationTerms
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $id;

    /**
     * @var BreedIndexType
     * @ORM\ManyToOne(targetEntity="BreedIndexType")
     * @ORM\JoinColumn(name="breed_index_type_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedIndexType")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $breedIndexType;

    /**
     * @var BreedValueType
     * @ORM\ManyToOne(targetEntity="BreedValueType")
     * @ORM\JoinColumn(name="breed_value_type_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\BreedValueType")
     * @JMS\Groups({"MIXBLUP"})
     */

    /**
     * @var ArrayCollection
     * @ORM\OrderBy({"en" = "ASC"})
     * @ORM\ManyToMany(targetEntity="BreedValueType")
     * @ORM\JoinTable(name="breed_index_term",
     *      joinColumns={@ORM\JoinColumn(name="breed_index_calculation_terms_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="breed_value_type_id", referencedColumnName="id")}
     * )
     * @JMS\Groups({"MIXBLUP"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\BreedValueType>")
     */
    private $breedValueTypes;

    /**
     * @var \DateTime
     *
     * @Assert\Date
     * @Assert\NotBlank
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @Assert\Date
     * @Assert\NotBlank
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @Assert\Date
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MIXBLUP"})
     */
    private $endDate;

    /**
     * BreedIndexCalculationTerms constructor.
     */
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
     * @return BreedIndexCalculationTerms
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return BreedIndexType
     */
    public function getBreedIndexType()
    {
        return $this->breedIndexType;
    }

    /**
     * @param BreedIndexType $breedIndexType
     * @return BreedIndexCalculationTerms
     */
    public function setBreedIndexType($breedIndexType)
    {
        $this->breedIndexType = $breedIndexType;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getBreedValueTypes()
    {
        return $this->breedValueTypes;
    }

    /**
     * @param ArrayCollection $breedValueTypes
     * @return BreedIndexCalculationTerms
     */
    public function setBreedValueTypes($breedValueTypes)
    {
        $this->breedValueTypes = $breedValueTypes;
        return $this;
    }

    /**
     * @param BreedValueType $breedValueType
     * @return BreedIndexCalculationTerms
     */
    public function addBreedValueType($breedValueType)
    {
        $this->breedValueTypes->add($breedValueType);
        return $this;
    }

    /**
     * @param BreedValueType $breedValueType
     * @return BreedIndexCalculationTerms
     */
    public function removeBreedValueType($breedValueType)
    {
        $this->breedValueTypes->removeElement($breedValueType);
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
     * @return BreedIndexCalculationTerms
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
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
     * @return BreedIndexCalculationTerms
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
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
     * @return BreedIndexCalculationTerms
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }


}