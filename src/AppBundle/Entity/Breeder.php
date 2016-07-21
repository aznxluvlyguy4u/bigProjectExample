<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Breeder
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BreederRepository")
 * @package AppBundle\Entity
 */
class Breeder {

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
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breederNumber;
  
    /**
     * @var CompanyAddress
     *
     * @ORM\OneToOne(targetEntity="CompanyAddress", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\CompanyAddress")
     */
    private $address;

    /**
     * Breeder constructor.
     */
    public function __construct() {
      
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
     * Set breederNumber
     *
     * @param string $breederNumber
     *
     * @return Breeder
     */
    public function setBreederNumber($breederNumber)
    {
        $this->breederNumber = $breederNumber;

        return $this;
    }

    /**
     * Get breederNumber
     *
     * @return string
     */
    public function getBreederNumber()
    {
        return $this->breederNumber;
    }

    /**
     * Set address
     *
     * @param \AppBundle\Entity\CompanyAddress $address
     *
     * @return Breeder
     */
    public function setAddress(\AppBundle\Entity\CompanyAddress $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\CompanyAddress
     */
    public function getAddress()
    {
        return $this->address;
    }
}
