<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BreedValueGraphGroup
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedValueGraphGroupRepository")
 * @package AppBundle\Entity
 */
class BreedValueGraphGroup
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
     * Currently used only to label the groups, not to order them by.
     *
     * @var integer
     * @ORM\Column(type="integer", nullable=false, unique=true)
     * @JMS\Type("integer")
     */
    private $ordinal;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $color;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\BreedValueType", mappedBy="graphGroup")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\BreedValueType>")
     */
    private $breedValueTypes;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return BreedValueGraphGroup
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrdinal()
    {
        return $this->ordinal;
    }

    /**
     * @param int $ordinal
     * @return BreedValueGraphGroup
     */
    public function setOrdinal($ordinal)
    {
        $this->ordinal = $ordinal;
        return $this;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     * @return BreedValueGraphGroup
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getBreedValueTypes()
    {
        if ($this->breedValueTypes === null) {
            $this->breedValueTypes = new ArrayCollection();
        }
        return $this->breedValueTypes;
    }

    /**
     * @param ArrayCollection $breedValueTypes
     * @return BreedValueGraphGroup
     */
    public function setBreedValueTypes($breedValueTypes)
    {
        $this->getBreedValueTypes()->add($breedValueTypes);
        return $this;
    }


}