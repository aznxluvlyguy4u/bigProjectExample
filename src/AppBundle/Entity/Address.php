<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\NullChecker;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Address
 * @ORM\Table(name="address")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AddressRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"CompanyAddress" = "CompanyAddress", "BillingAddress" = "BillingAddress", "LocationAddress" = "LocationAddress"})
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                        "CompanyAddress" : "AppBundle\Entity\CompanyAddress",
 *                        "BillingAddress" : "AppBundle\Entity\BillingAddress",
 *                       "LocationAddress" : "AppBundle\Entity\LocationAddress"},
 *     groups = {
 *     "BASIC",
 *     "CONTACT_INFO"
 * })
 *
 * @package AppBundle\Entity
 */
abstract class Address
{
    use EntityClassInfo;

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $streetName;

  /**
   * @var int
   *
   * @ORM\Column(type="integer")
   * @Assert\NotBlank
   * @JMS\Type("integer")
   */
  private $addressNumber;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   */
  private $addressNumberSuffix;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @Assert\Regex("/([0-9]{4}[A-Z]{2})\b/")
   * @Assert\Length(max = 6)
   * @JMS\Type("string")
   */
  private $postalCode;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $city;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $country;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   */
  private $state;

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
     * Set streetName
     *
     * @param string $streetName
     *
     * @return Address
     */
    public function setStreetName($streetName)
    {
        $this->streetName = trim($streetName);

        return $this;
    }

    /**
     * Get streetName
     *
     * @return string
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * Set addressNumber
     *
     * @param integer $addressNumber
     *
     * @return Address
     */
    public function setAddressNumber($addressNumber)
    {
        $this->addressNumber = trim($addressNumber);

        return $this;
    }

    /**
     * Get addressNumber
     *
     * @return integer
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * Set addressNumberSuffix
     *
     * @param string $addressNumberSuffix
     *
     * @return Address
     */
    public function setAddressNumberSuffix($addressNumberSuffix)
    {
        $this->addressNumberSuffix = trim($addressNumberSuffix);

        return $this;
    }

    /**
     * Get addressNumberSuffix
     *
     * @return string
     */
    public function getAddressNumberSuffix()
    {
        return $this->addressNumberSuffix;
    }

    /**
     * Set postalCode
     *
     * @param string $postalCode
     *
     * @return Address
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = trim(strtoupper($postalCode));

        return $this;
    }

    /**
     * Get postalCode
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = trim(strtoupper($city));

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Address
     */
    public function setCountry($country)
    {
        $this->country = trim($country);

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Address
     */
    public function setState($state)
    {
        $this->state = trim($state);

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }


    /**
     * @return null|string
     */
    public function getFullStreetNameAndNumber()
    {
        if(NullChecker::isNull($this->streetName)) { return null; }
        $result = $this->streetName;
        if(NullChecker::isNotNull($this->addressNumber)) { $result = $result.' '.$this->addressNumber; };
        if(NullChecker::isNotNull($this->addressNumberSuffix)) { $result = $result.$this->addressNumberSuffix; };
        return $result;
    }
}
