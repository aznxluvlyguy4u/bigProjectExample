<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BreederNumber
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreederNumberRepository")
 * @package AppBundle\Entity
 */
class BreederNumber
{
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
     * @ORM\Column(type="string", unique=true, nullable=false)
     * @Assert\Length(min = 5, max = 5)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $breederNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $ubnOfBirth;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $source;

    /**
     * BreederNumber constructor.
     * @param string $breederNumber
     * @param string $ubnOfBirth
     */
    public function __construct($breederNumber, $ubnOfBirth)
    {
        $this->breederNumber = $breederNumber;
        $this->ubnOfBirth = $ubnOfBirth;
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
    public function getBreederNumber()
    {
        return $this->breederNumber;
    }

    /**
     * @param string $breederNumber
     */
    public function setBreederNumber($breederNumber)
    {
        $this->breederNumber = $breederNumber;
    }

    /**
     * @return string
     */
    public function getUbnOfBirth()
    {
        return $this->ubnOfBirth;
    }

    /**
     * @param string $ubnOfBirth
     */
    public function setUbnOfBirth($ubnOfBirth)
    {
        $this->ubnOfBirth = $ubnOfBirth;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }



}