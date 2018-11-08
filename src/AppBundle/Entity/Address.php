<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\NullChecker;
use AppBundle\Util\StringUtil;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

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
 *     "CONTACT_INFO",
 *     "DOSSIER"
 *
 * })
 *
 * @package AppBundle\Entity
 */
abstract class Address
{
    use EntityClassInfo;

    const IS_DUTCH_COUNTRY_DEFAULT_BOOLEAN = true;

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   * @JMS\Type("integer")
   * @JMS\Groups({
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY"
   * })
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $streetName;

  /**
   * @var int
   *
   * @ORM\Column(type="integer")
   * @Assert\NotBlank
   * @JMS\Type("integer")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $addressNumber;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $addressNumberSuffix;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @Assert\Length(max = 6)
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $postalCode;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $city;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $country;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ADDRESS",
   *     "INVOICE",
   *     "INVOICE_NO_COMPANY",
   *     "DOSSIER"
   * })
   */
  private $state;


    /**
     * @var Country
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Country", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Country")
     * @JMS\Groups({
     *     "ADDRESS",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "DOSSIER"
     * })
     */
  private $countryDetails;

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
        $this->addressNumberSuffix = StringUtil::trimIfNotNull($addressNumberSuffix);

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
     * WARNING THIS IS THE SETTING FOR AN OBSOLETE VARIABLE! Use setCountryDetails instead.
     *
     * Set country
     *
     * @param string $country
     *
     * @return Address
     */
    protected function setCountry($country)
    {
        $this->country = trim($country);

        return $this;
    }

    /**
     * Get country id
     *
     * @return int|null
     */
    public function getCountryId(): ?int
    {
        return $this->countryDetails ? $this->countryDetails->getId() : null;
    }

    /**
     * Get country
     *
     * @return string|null
     */
    public function getCountryName(): ?string
    {
        return $this->countryDetails ? $this->countryDetails->getName() : null;
    }

    /**
     * @return Country
     */
    public function getCountryDetails(): Country
    {
        return $this->countryDetails;
    }

    /**
     * @param Country $countryDetails
     * @return Address
     */
    public function setCountryDetails(Country $countryDetails): Address
    {
        $this->countryDetails = $countryDetails;
        $this->setCountry($countryDetails->getName());
        return $this;
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
        if (empty($state)) {
            $this->state = null;
        } else {
            $this->state = StringUtil::trimIfNotNull($state);
        }

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
     * @return bool
     */
    public function isDutchAddress(): bool
    {
        return $this->getCountryCode() ? $this->getCountryCode() === \AppBundle\Enumerator\Country::NL
            : Address::IS_DUTCH_COUNTRY_DEFAULT_BOOLEAN;
    }


    /**
     * @return null|string
     */
    public function getCountryCode(): ?string
    {
        return $this->getCountryDetails() ? $this->getCountryDetails()->getCode() : null;
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
