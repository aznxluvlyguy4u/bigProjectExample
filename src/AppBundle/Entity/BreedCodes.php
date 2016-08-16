<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\Tests\Compiler\A;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedCodes
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedCodesRepository")
 * @package AppBundle\Entity
 */
class BreedCodes
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection
     * @JMS\Type("AppBundle\Entity\BreedCode")
     * @ORM\OneToMany(targetEntity="BreedCode", mappedBy="codeSet", cascade={"persist"})
     */
    private $codes;
    
    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal", mappedBy="breedCodes", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    public function __construct()
    {
        $this->codes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
    }

    /**
     * @return ArrayCollection
     */
    public function getCodes()
    {
        return $this->codes;
    }

    /**
     * @param ArrayCollection $codes
     */
    public function setCodes($codes)
    {
        $this->codes = $codes;
    }

    /**
     * Add breedCode
     *
     * @param BreedCode $breedCode
     *
     * @return BreedCodes
     */
    public function addCode(BreedCode $breedCode)
    {
        $this->codes[] = $breedCode;

        return $this;
    }

    /**
     * Remove breedCode
     *
     * @param BreedCode $breedCode
     */
    public function removeCode(BreedCode $breedCode)
    {
        $this->codes->removeElement($breedCode);
    }


    /**
     * Remove all codes
     */
    public function removeAllCodes()
    {
        $this->codes->clear();
    }

}