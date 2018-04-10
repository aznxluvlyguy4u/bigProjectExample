<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Invoice
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRepository")
 * @package AppBundle\Entity
 */
class Invoice
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    protected $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string",  unique=true, nullable=true)
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $invoiceNumber;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $invoiceDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": "UNPAID"})
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $documentUrl;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\InvoiceRuleSelection", mappedBy="invoice", cascade={"persist", "remove"}, orphanRemoval=true)
     * @JMS\Type("ArrayCollection<AppBundle\Entity\InvoiceRuleSelection>")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $invoiceRuleSelections;

    /**
     * @var float
     * @ORM\Column(type="float", name="total", nullable=true)
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $total;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Company")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $company;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_local_id", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $companyLocalId;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_name", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $companyName;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_vat_number", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $companyVatNumber;

    /**
     * @var Address $companyAddress
     * @ORM\ManyToOne(targetEntity="Address")
     * @ORM\JoinColumn(name="company_address_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Address")
     */
    private $companyAddress;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_debtor_number", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $companyDebtorNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="mollie_id", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $mollieId;

    /**
     * @var InvoiceSenderDetails
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\InvoiceSenderDetails")
     * @ORM\JoinColumn(name="invoice_sender_details_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\InvoiceSenderDetails")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $senderDetails;

    /**
     * @var string
     * @ORM\Column(type="string", name="ubn", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $ubn;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="paid_date", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $paidDate;
    /**
     * @var bool
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $isDeleted = false;

    /**
     * Invoice constructor.
     */
    public function __construct()
    {
        $this->initializeInvoiceRuleSelection();
    }

    public function getId() {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getPaidDate()
    {
        return $this->paidDate;
    }

    /**
     * @param DateTime $paidDate
     */
    public function setPaidDate($paidDate)
    {
        $this->paidDate = $paidDate;
    }

    /**
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
    }

    /**
     * @return DateTime
     */
    public function getInvoiceDate()
    {
        return $this->invoiceDate;
    }

    /**
     * @param DateTime $invoiceDate
     */
    public function setInvoiceDate($invoiceDate)
    {
        $this->invoiceDate = $invoiceDate;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getDocumentUrl()
    {
        return $this->documentUrl;
    }

    /**
     * @param string $documentUrl
     */
    public function setDocumentUrl($documentUrl)
    {
        $this->documentUrl = $documentUrl;
    }


    private function initializeInvoiceRuleSelection()
    {
        if ($this->invoiceRuleSelections === null) {
            $this->invoiceRuleSelections = new ArrayCollection();
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getInvoiceRuleSelections()
    {
        $this->initializeInvoiceRuleSelection();
        return $this->invoiceRuleSelections;
    }

    /**
     * @param ArrayCollection $invoiceRuleSelections
     * @return Invoice
     */
    public function setInvoiceRuleSelections($invoiceRuleSelections)
    {
        $this->invoiceRuleSelections = $invoiceRuleSelections;
        return $this;
    }

    /**
     * @return Address
     */
    public function getCompanyAddress()
    {
        return $this->companyAddress;
    }

    /**
     * @param Address $companyAddress
     */
    public function setCompanyAddress($companyAddress)
    {
        $this->companyAddress = $companyAddress;
    }

    /**
     * @param InvoiceRuleSelection $invoiceRuleSelection
     * @return Invoice
     */
    public function addInvoiceRuleSelection(InvoiceRuleSelection $invoiceRuleSelection)
    {
        $this->initializeInvoiceRuleSelection();
        $this->invoiceRuleSelections->add($invoiceRuleSelection);
        return $this;
    }

    /**
     * @param InvoiceRuleSelection $invoiceRuleSelection
     * @return Invoice
     */
    public function removeInvoiceRuleSelection(InvoiceRuleSelection $invoiceRuleSelection)
    {
        $this->initializeInvoiceRuleSelection();
        $this->invoiceRuleSelections->removeElement($invoiceRuleSelection);
        return $this;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param float $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return bool
     */
    public function isDeleted()
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

    /**
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * @param string $ubn
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @return string
     */
    public function getCompanyLocalId()
    {
        return $this->companyLocalId;
    }

    /**
     * @param string $companyLocalId
     */
    public function setCompanyLocalId($companyLocalId)
    {
        $this->companyLocalId = $companyLocalId;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string
     */
    public function getCompanyVatNumber()
    {
        return $this->companyVatNumber;
    }

    /**
     * @param string $companyVatNumber
     */
    public function setCompanyVatNumber($companyVatNumber)
    {
        $this->companyVatNumber = $companyVatNumber;
    }

    /**
     * @return InvoiceSenderDetails
     */
    public function getSenderDetails()
    {
        return $this->senderDetails;
    }

    /**
     * @param InvoiceSenderDetails $senderDetails
     */
    public function setSenderDetails($senderDetails)
    {
        $this->senderDetails = $senderDetails;
    }

    /**
     * @return string
     */
    public function getCompanyDebtorNumber()
    {
        return $this->companyDebtorNumber;
    }

    /**
     * @param string $companyDebtorNumber
     */
    public function setCompanyDebtorNumber($companyDebtorNumber)
    {
        $this->companyDebtorNumber = $companyDebtorNumber;
    }

    /**
     * @return string
     */
    public function getMollieId()
    {
        return $this->mollieId;
    }

    /**
     * @param string $mollieId
     */
    public function setMollieId($mollieId)
    {
        $this->mollieId = $mollieId;
    }

    public function copyValues(Invoice $invoice){
        $this->setCompany($invoice->getCompany());
        $this->setCompanyLocalId($invoice->getCompanyLocalId());
        $this->setCompanyName($invoice->getCompanyName());
        $this->setCompanyVatNumber($invoice->getCompanyVatNumber());
        $this->setCompanyDebtorNumber($invoice->getCompanyDebtorNumber());
        $this->setUbn($invoice->getUbn());
        $this->setTotal($invoice->getTotal());
        $this->setDocumentUrl($invoice->getDocumentUrl());
        $this->setInvoiceDate($invoice->getInvoiceDate());
        $this->setInvoiceNumber($invoice->getInvoiceNumber());
        $this->setStatus($invoice->getStatus());
    }
}