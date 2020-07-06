<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

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
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $emailAddress;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    private $addressNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $ubn;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $brs;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $phoneNumber;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address): void
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getAddressNumber()
    {
        return $this->addressNumber;
    }

    /**
     * @param mixed $addressNumber
     */
    public function setAddressNumber($addressNumber): void
    {
        $this->addressNumber = $addressNumber;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param mixed $postalCode
     */
    public function setPostalCode($postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * @param mixed $ubn
     */
    public function setUbn($ubn): void
    {
        $this->ubn = $ubn;
    }

    /**
     * @return mixed
     */
    public function getBrs()
    {
        return $this->brs;
    }

    /**
     * @param mixed $brs
     */
    public function setBrs($brs): void
    {
        $this->brs = $brs;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param mixed $phoneNumber
     */
    public function setPhoneNumber($phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getFullAddress()
    {
        return $this->address .' '. $this->addressNumber;
    }

    public function getFullName()
    {
        return $this->firstName .' '. $this->lastName;
    }
}