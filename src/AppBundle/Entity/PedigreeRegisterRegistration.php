<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class PedigreeRegisterRegistration
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PedigreeRegisterRegistrationRepository")
 * @package AppBundle\Entity
 */
class PedigreeRegisterRegistration
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     * })
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     * })
     */
    private $breederNumber;

    /**
     * @var Location
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Location", inversedBy="pedigreeRegisterRegistrations")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Location>")
     * @JMS\Groups({
     * })
     */
    private $location;

    /**
     * @var PedigreeRegister
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\PedigreeRegister")
     * @ORM\JoinColumn(name="pedigree_register_id", referencedColumnName="id")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\PedigreeRegister>")
     * @JMS\Groups({
     * })
     */
    private $pedigreeRegister;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return PedigreeRegisterRegistration
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * @return PedigreeRegisterRegistration
     */
    public function setBreederNumber($breederNumber)
    {
        $this->breederNumber = $breederNumber;
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     * @return PedigreeRegisterRegistration
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return PedigreeRegister
     */
    public function getPedigreeRegister()
    {
        return $this->pedigreeRegister;
    }

    /**
     * @param PedigreeRegister $pedigreeRegister
     * @return PedigreeRegisterRegistration
     */
    public function setPedigreeRegister($pedigreeRegister)
    {
        $this->pedigreeRegister = $pedigreeRegister;
        return $this;
    }


}