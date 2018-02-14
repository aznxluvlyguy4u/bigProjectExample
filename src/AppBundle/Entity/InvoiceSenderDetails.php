<?php

namespace AppBundle\Entity;

use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InvoiceSenderDetails
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceSenderDetailsRepository")
 * @ORM\Table(name="invoice_sender_details")
 */
class InvoiceSenderDetails
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string",name="name", nullable=true)
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="iban")
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $iban;

    /**
     * @var string
     * @ORM\Column(type="string", name="chamber_of_commerce_number")
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $chamberOfCommerceNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="vat_number")
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $vatNumber;

    /**
     * @var Address
     * @ORM\OneToOne(targetEntity="Address", cascade={"persist"})
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $address;

    /**
     * @var int
     * @ORM\Column(type="integer", name="payment_deadline_in_days")
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $paymentDeadlineInDays;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_deleted")
     * @JMS\Groups({
     *     "INVOICE_SENDER_DETAILS"
     * })
     */
    private $isDeleted = false;

    public function __construct()
    {
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * @param string $iban
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
    }

    /**
     * @return int
     */
    public function getChamberOfCommerceNumber()
    {
        return $this->chamberOfCommerceNumber;
    }

    /**
     * @param int $chamberOfCommerceNumber
     */
    public function setChamberOfCommerceNumber($chamberOfCommerceNumber)
    {
        $this->chamberOfCommerceNumber = $chamberOfCommerceNumber;
    }

    /**
     * @return string
     */
    public function getvatNumber()
    {
        return $this->vatNumber;
    }

    /**
     * @param string $vatNumber
     */
    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;
    }

    public function getAddress(){
        return $this->address;
    }

    public function setAddress($address){
        $this->address = $address;
    }

    /**
     * @return int
     */
    public function getPaymentDeadlineInDays()
    {
        return $this->paymentDeadlineInDays;
    }

    /**
     * @param int $paymentDeadlineInDays
     */
    public function setPaymentDeadlineInDays($paymentDeadlineInDays)
    {
        $this->paymentDeadlineInDays = $paymentDeadlineInDays;
    }

    /**
     * @return bool
     */
    public function isIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param bool $isDeleted
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    }

    public function copyValues(InvoiceSenderDetails $invoiceSenderDetails){
        $this->setName($invoiceSenderDetails->getName());
        $this->setVatNumber($invoiceSenderDetails->getvatNumber());
        $this->setIban($invoiceSenderDetails->getIban());
        $this->setChamberOfCommerceNumber($invoiceSenderDetails->getChamberOfCommerceNumber());
        $this->setPaymentDeadlineInDays($invoiceSenderDetails->getPaymentDeadlineInDays());
        $this->setAddress($invoiceSenderDetails->getAddress());
    }

    /**
     * @return bool
     */
    public function containsAllNecessaryData() {
        return
            $this->getName() !== null &&
            $this->getIban() !== null &&
            $this->getChamberOfCommerceNumber() !== null &&
            $this->getvatNumber() !== null &&
            $this->getPaymentDeadlineInDays() !== null &&
            $this->getAddress() !== null &&
            $this->getAddress()->getAddressNumber() !== null &&
            $this->getAddress()->getStreetName() !== null &&
            $this->getAddress()->getPostalCode() !== null &&
            $this->getAddress()->getCity() !== null &&
            $this->getAddress()->getCountry() !== null &&
            $this->getName() !== '' &&
            $this->getIban() !== '' &&
            $this->getChamberOfCommerceNumber() !== '' &&
            $this->getvatNumber() !== '' &&
            $this->getPaymentDeadlineInDays() !== '' &&
            $this->getAddress()->getAddressNumber() !== '' &&
            $this->getAddress()->getStreetName() !== '' &&
            $this->getAddress()->getPostalCode() !== '' &&
            $this->getAddress()->getCity() !== '' &&
            $this->getAddress()->getCountry() !== ''
        ;
    }


    /**
     * @return array
     */
    public function getMissingNecessaryVariables()
    {
        $vars = [];

        if ($this->getName() === null ||
            $this->getName() === '') {
            $vars[] = 'NAME';
        }

        if ($this->getIban() === null ||
            $this->getIban() === '') {
            $vars[] = 'IBAN';
        }

        if ($this->getChamberOfCommerceNumber() === null ||
            $this->getChamberOfCommerceNumber() === '') {
            $vars[] = 'CHAMBER OF COMMERCE NUMBER';
        }

        if ($this->getvatNumber() === null ||
            $this->getvatNumber() === '') {
            $vars[] = 'VAT NUMBER';
        }

        if ($this->getPaymentDeadlineInDays() === null ||
            $this->getPaymentDeadlineInDays() === '') {
            $vars[] = 'PAYMENT DEADLINE IN DAYS';
        }

        if ($this->getAddress() === null) {
            $vars[] = 'ADDRESS NUMBER';
            $vars[] = 'STREET NAME';
            $vars[] = 'POSTAL CODE';
            $vars[] = 'CITY';
            $vars[] = 'COUNTRY';
        } else {
            if ($this->getAddress()->getAddressNumber() === null ||
                $this->getAddress()->getAddressNumber() === '') {
                $vars[] = 'ADDRESS NUMBER';
            }
            if ($this->getAddress()->getStreetName() === null ||
                $this->getAddress()->getStreetName() === '') {
                $vars[] = 'STREET NAME';
            }
            if ($this->getAddress()->getPostalCode() === null ||
                $this->getAddress()->getPostalCode() === '') {
                $vars[] = 'POSTAL CODE';
            }
            if ($this->getAddress()->getCity() === null ||
                $this->getAddress()->getCity() === '') {
                $vars[] = 'CITY';
            }
            if ($this->getAddress()->getCountry() === null ||
                $this->getAddress()->getCountry() === '') {
                $vars[] = 'COUNTRY';
            }
        }

        return $vars;
    }
}