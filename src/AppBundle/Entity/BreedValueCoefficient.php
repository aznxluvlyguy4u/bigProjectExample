<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreedValueCoefficient
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedValueCoefficientRepository")
 * @package AppBundle\Entity
 */
class BreedValueCoefficient {
    /**
    * @var integer
    *
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;
    
    /**
    * @var string
    *
    * @ORM\Column(type="string")
    * @Assert\NotBlank
    * @JMS\Type("string")
    */
    private $indexType;
    
    /**
    * @var string
    *
    * @ORM\Column(type="string")
    * @Assert\NotBlank
    * @JMS\Type("string")
    */
    private $trait;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     */
    private $value;


    /**
     * BreedValueCoefficient constructor.
     * @param int $indexType
     * @param int $trait
     * @param int $value
     */
    public function __construct($indexType = 0, $trait = 0, $value = 0)
    {
        $this->setIndexType($indexType);
        $this->setTrait($trait);
        $this->setValue($value);
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
     * @return string
     */
    public function getIndexType()
    {
        return $this->indexType;
    }

    /**
     * @param string $indexType
     */
    public function setIndexType($indexType)
    {
        $this->indexType = $indexType;
    }

    /**
     * @return string
     */
    public function getTrait()
    {
        return $this->trait;
    }

    /**
     * @param string $trait
     */
    public function setTrait($trait)
    {
        $this->trait = $trait;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }


}
