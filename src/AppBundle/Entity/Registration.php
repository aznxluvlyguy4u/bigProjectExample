<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Registration
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RegistrationRepository")
 * @package AppBundle\Entity
 */
class Registration
{
    use EntityClassInfo;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $registrationId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="name.first.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="name.last.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="email.address.not_blank")
     * @Assert\Email(
     *     message = "email.address.invalid.format",
     *     checkMX = true
     * )
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $emailAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="address.street.name.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $streetName;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @JMS\Type("integer")
     * @Assert\NotBlank(message="address.number.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $addressNumber;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $addressNumberSuffix;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="address.postalcode.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="address.city.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="company.name.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $companyName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="ubn.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $ubn;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="brs.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $brs;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @Assert\NotBlank(message="phone.number.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $phoneNumber;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":false}, nullable=false)
     * @JMS\Type("boolean")
     * @Assert\NotBlank(message="auto.debit.not_blank")
     * @JMS\Groups({
     *     "REGISTRATION"
     * })
     */
    private $hasAutoDebit;

    /**
 * @var DateTime
 *
 * @ORM\Column(type="datetime", nullable=false)
 */
    private $createdAt;

    public function __construct()
    {
        $this->registrationId = Utils::generateTokenCode();
        $this->createdAt = new DateTime();
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
    public function getRegistrationId(): string
    {
        return $this->registrationId;
    }

    /**
     * @param string $registrationId
     * @return Registration
     */
    public function setRegistrationId(string $registrationId): self
    {
        $this->registrationId = $registrationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Registration
     */
    public function setStatus(string $status): Registration
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param  string  $firstName
     * @return Registration
     */
    public function setFirstName(string $firstName): Registration
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param  string  $lastName
     * @return Registration
     */
    public function setLastName(string $lastName): Registration
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * @param  string  $emailAddress
     * @return Registration
     */
    public function setEmailAddress(string $emailAddress): Registration
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getStreetName(): string
    {
        return $this->streetName;
    }

    /**
     * @param  string  $streetName
     * @return Registration
     */
    public function setStreetName(string $streetName): Registration
    {
        $this->streetName = $streetName;
        return $this;
    }

    /**
     * @return int
     */
    public function getAddressNumber(): int
    {
        return $this->addressNumber;
    }

    /**
     * @param  int  $addressNumber
     * @return Registration
     */
    public function setAddressNumber(int $addressNumber): Registration
    {
        $this->addressNumber = $addressNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddressNumberSuffix(): ?string
    {
        return $this->addressNumberSuffix;
    }

    /**
     * @param string|null $addressNumberSuffix
     * @return Registration
     */
    public function setAddressNumberSuffix(?string $addressNumberSuffix): self
    {
        $this->addressNumberSuffix = $addressNumberSuffix;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * @param  string  $postalCode
     * @return Registration
     */
    public function setPostalCode(string $postalCode): Registration
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param  string  $city
     * @return Registration
     */
    public function setCity(string $city): Registration
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     * @return Registration
     */
    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * @return string
     */
    public function getUbn(): string
    {
        return $this->ubn;
    }

    /**
     * @param  string  $ubn
     * @return Registration
     */
    public function setUbn(string $ubn): Registration
    {
        $this->ubn = $ubn;
        return $this;
    }

    /**
     * @return string
     */
    public function getBrs(): string
    {
        return $this->brs;
    }

    /**
     * @param  string  $brs
     * @return Registration
     */
    public function setBrs(string $brs): Registration
    {
        $this->brs = $brs;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param  string  $phoneNumber
     * @return Registration
     */
    public function setPhoneNumber(string $phoneNumber): Registration
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return Registration
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHasAutoDebit(): bool
    {
        return $this->hasAutoDebit;
    }


    /**
     * @param bool $hasAutoDebit
     * @return Registration
     */
    public function setHasAutoDebit(bool $hasAutoDebit): Registration
    {
        $this->hasAutoDebit = $hasAutoDebit;
        return $this;
    }

    public function getFullAddress(): string
    {
        return $this->streetName .' '. $this->addressNumber.$this->addressNumberSuffix;
    }

    public function getFullName(): string
    {
        return $this->firstName .' '. $this->lastName;
    }
}
