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
 * Class BreedCode
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreedCodeRepository")
 * @package AppBundle\Entity
 */
class BreedCode
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
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $value;

    /**
     * @var BreedCode
     * @ORM\ManyToOne(targetEntity="BreedCodes", inversedBy="codes", cascade={"persist"})
     * @ORM\JoinColumn(name="breed_codes_id", referencedColumnName="id")
     */
    private $codeSet;


    /**
     * BreedCode constructor.
     * @param BreedCodes $codeSet
     * @param string $name
     * @param int $value
     */
    public function __construct(BreedCodes $codeSet, $name, $value)
    {
        $this->codeSet = $codeSet;
        $this->name = $name;
        $this->value = $value;
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
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return BreedCodes
     */
    public function getCodeSet()
    {
        return $this->codeSet;
    }

    /**
     * @param BreedCodes $codeSet
     */
    public function setCodeSet($codeSet)
    {
        $this->codeSet = $codeSet;
    }




}