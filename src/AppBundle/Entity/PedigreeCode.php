<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class PedigreeCode
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PedigreeCodeRepository")
 * @package AppBundle\Entity
 */
class PedigreeCode
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @Assert\Regex("/([A-Z]{2})\b/")
     * @Assert\Length(max = 2)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $fullName;

    /**
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     */
    private $isValidated;

    /**
     * PedigreeCode constructor.
     * @param string $code
     * @param string $fullName
     * @param boolean $isValidated
     */
    public function __construct($code, $fullName, $isValidated = false)
    {
        $this->code = $code;
        $this->fullName = $fullName;
        $this->isValidated = $isValidated;
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     * @return boolean
     */
    public function isValidated()
    {
        return $this->isValidated;
    }

    /**
     * @param boolean $isValidated
     */
    public function setIsValidated($isValidated)
    {
        $this->isValidated = $isValidated;
    }



}