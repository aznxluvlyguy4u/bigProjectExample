<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Province
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ProvinceRepository")
 * @package AppBundle\Entity
 */
class Province {
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
    * @Assert\Regex("/([A-Z]{2})\b/")
    * @Assert\Length(max = 2)
    * @Assert\NotBlank
    * @JMS\Type("string")
    */
    private $code;

    /**
    * @var Country
    *
    * @ORM\Column(type="string")
    * @Assert\NotBlank
    * @JMS\Type("string")
    */
    private $name;

    /**
    * @var string
    *
    * @ORM\ManyToOne(targetEntity="Country")
    * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
    */
    private $country;

    public function __construct(Country $country = null, $name = null, $code = null)
    {
        $this->country = $country;
        $this->code = strtoupper($code);
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
        $this->code = strtoupper($code);
    }

    /**
     * @return Country
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Country $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }


}
