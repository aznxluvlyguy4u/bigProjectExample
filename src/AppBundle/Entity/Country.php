<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Country
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CountryRepository")
 * @package AppBundle\Entity
 */
class Country {

    use EntityClassInfo;

  /**
   * @var integer
   *
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="IDENTITY")
   * @JMS\Groups({
   *     "BASIC",
   *     "DETAILS"
   * })
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
   * @JMS\Groups({
   *     "BASIC",
   *     "DETAILS"
   * })
   */
  private $code;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "BASIC",
   *     "DETAILS"
   * })
   */
  private $name;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "BASIC",
   *     "DETAILS"
   * })
   */
  private $continent;

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
     * Set code
     *
     * @param string $code
     *
     * @return Country
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set continent
     *
     * @param string $continent
     *
     * @return Country
     */
    public function setContinent($continent)
    {
        $this->continent = $continent;

        return $this;
    }

    /**
     * Get continent
     *
     * @return string
     */
    public function getContinent()
    {
        return $this->continent;
    }
}
