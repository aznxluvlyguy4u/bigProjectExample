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
     *     "INVOICE"
     * })
     */
    protected $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string",  unique=true, nullable=true)
     * @JMS\Groups({
     *     "INVOICE"
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
     *     "INVOICE"
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
     *     "INVOICE"
     * })
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $documentUrl;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InvoiceRule", mappedBy="invoice", cascade={"persist"})
     * @JMS\Type("ArrayCollection")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $invoiceRules;

    /**
     * @var float
     * @ORM\Column(type="float", name="total", nullable=true)
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE"
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
     *     "INVOICE"
     * })
     */
    private $companyLocalId;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_name", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $companyName;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_vat_number", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $companyVatNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_debtor_number", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $companyDebtorNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="mollie_id", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $mollieId;

    /**
     * @var InvoiceSenderDetails
     * @ORM\ManyToOne(targetEntity="InvoiceSenderDetails")
     * @ORM\JoinColumn(name="invoice_invoice_sender_details_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\InvoiceSenderDetails")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $senderDetails;

    /**
     * @var string
     * @ORM\Column(type="string", name="ubn", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $ubn;


    /**
     * @var bool
     * @ORM\Column(name="is_deleted", type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "INVOICE"
     * })
     */
    private $isDeleted = false;

    /**
     * Invoice constructor.
     */
    public function __construct()
    {
        $this->invoiceRules = new ArrayCollection();
    }

    public function getId() {
        return $this->id;
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

    /**
     * @return ArrayCollection
     */
    public function getInvoiceRules()
    {
        return $this->invoiceRules;
    }

    /**
     * @param ArrayCollection $invoiceRules
     */
    public function setInvoiceRules($invoiceRules)
    {
        $this->invoiceRules = $invoiceRules;
    }

    public function addInvoiceRule(InvoiceRule $invoiceRule){
        $this->invoiceRules[] = $invoiceRule;
    }

    public function removeInvoiceRule(InvoiceRule $invoiceRule){
        $this->invoiceRules->removeElement($invoiceRule);
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