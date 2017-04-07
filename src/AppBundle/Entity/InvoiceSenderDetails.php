<?php
/**
 * Created by IntelliJ IDEA.
 * User: werner
 * Date: 7-4-17
 * Time: 10:25
 */

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
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", name="iban")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $iban;

    /**
     * @var int
     * @ORM\Column(type="integer", name="kvk_number")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $kvkNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="btw_number")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $btwNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="street_name")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $streetName;

    /**
     * @var integer
     * @ORM\Column(type="integer", name="street_number")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $streetNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="street_number_suffix")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $streetNumberSuffix;

    /**
     * @var string
     * @ORM\Column(type="string", name="postal_code")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $postalCode;

    /**
     * @var int
     * @ORM\Column(type="integer", name="payment_deadline_in_days")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
     */
    private $paymentDeadlineInDays;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="is_deleted")
     * @JMS\Groups({"INVOICE_SENDER_DETAILS"})
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
    public function getKvkNumber()
    {
        return $this->kvkNumber;
    }

    /**
     * @param int $kvkNumber
     */
    public function setKvkNumber($kvkNumber)
    {
        $this->kvkNumber = $kvkNumber;
    }

    /**
     * @return string
     */
    public function getBtwNumber()
    {
        return $this->btwNumber;
    }

    /**
     * @param string $btwNumber
     */
    public function setBtwNumber($btwNumber)
    {
        $this->btwNumber = $btwNumber;
    }

    /**
     * @return string
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * @param string $streetName
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;
    }

    /**
     * @return int
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @param int $streetNumber
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
    }

    /**
     * @return string
     */
    public function getStreetNumberSuffix()
    {
        return $this->streetNumberSuffix;
    }

    /**
     * @param string $streetNumberSuffix
     */
    public function setStreetNumberSuffix($streetNumberSuffix)
    {
        $this->streetNumberSuffix = $streetNumberSuffix;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
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


}