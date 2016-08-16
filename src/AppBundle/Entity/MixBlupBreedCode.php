<?php

namespace AppBundle\Entity;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Tag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TagRepository")
 * @package AppBundle\Entity
 */
class MixBlupBreedCode
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;

    /**
     * @var integer
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $TE;

    /**
     * @var integer
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $CF;

    /**
     * @var integer
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $NH;

    /**
     * @var integer
     *
     * @Assert\NotBlank
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $OV;

    /**
     * @var Animal
     * @ORM\OneToOne(targetEntity="Animal", mappedBy="mixBlupBreedCode", cascade={"persist"})
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    public function __construct($te = 0, $cf = 0, $nh = 0, $ov = 0)
    {
        $this->TE = $te;
        $this->CF = $cf;
        $this->NH = $nh;
        $this->OV = $ov;
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
     * @return int
     */
    public function getTE()
    {
        return $this->TE;
    }

    /**
     * @param int $TE
     */
    public function setTE($TE)
    {
        $this->TE = $TE;
    }

    /**
     * @return int
     */
    public function getCF()
    {
        return $this->CF;
    }

    /**
     * @param int $CF
     */
    public function setCF($CF)
    {
        $this->CF = $CF;
    }

    /**
     * @return int
     */
    public function getNH()
    {
        return $this->NH;
    }

    /**
     * @param int $NH
     */
    public function setNH($NH)
    {
        $this->NH = $NH;
    }

    /**
     * @return int
     */
    public function getOV()
    {
        return $this->OV;
    }

    /**
     * @param int $OV
     */
    public function setOV($OV)
    {
        $this->OV = $OV;
    }


}