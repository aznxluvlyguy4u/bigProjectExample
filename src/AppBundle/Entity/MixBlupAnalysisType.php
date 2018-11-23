<?php


namespace AppBundle\Entity;


use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class MixBlupAnalysisType
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MixBlupAnalysisTypeRepository")
 * @package AppBundle\Entity
 */
class MixBlupAnalysisType
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $en;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $nl;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\BreedValueType", mappedBy="mixBlupAnalysisType")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\BreedValueType>")
     */
    private $breedValueTypes;


    public function __construct()
    {
        $this->breedValueTypes = new ArrayCollection();
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
     * @return MixBlupAnalysisType
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getEn()
    {
        return $this->en;
    }

    /**
     * @param string $en
     * @return MixBlupAnalysisType
     */
    public function setEn($en)
    {
        $this->en = $en;
        return $this;
    }

    /**
     * @return string
     */
    public function getNl()
    {
        return $this->nl;
    }

    /**
     * @param string $nl
     * @return MixBlupAnalysisType
     */
    public function setNl($nl)
    {
        $this->nl = $nl;
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
     * @return MixBlupAnalysisType
     */
    public function setBreedValueTypes($breedValueTypes)
    {
        $this->breedValueTypes = $breedValueTypes;
        return $this;
    }

    /**
     * @param BreedValueType $breedValueType
     * @return $this
     */
    public function addBreedValueType($breedValueType)
    {
        $this->breedValueTypes->add($breedValueType);
        return $this;
    }

    /**
     * @param BreedValueType $breedValueType
     * @return $this
     */
    public function removeBreedValueType($breedValueType)
    {
        $this->breedValueTypes->removeElement($breedValueType);
        return $this;
    }
}