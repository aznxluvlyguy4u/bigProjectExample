<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareBirth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBirthRepository")
 * @package AppBundle\Entity
 */
class DeclareBirth extends DeclareBase
{
//TODO
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="births", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareBirth
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareBirth
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }
}
