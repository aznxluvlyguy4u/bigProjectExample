<?php

namespace AppBundle\Entity;

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
class Invoice {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"INVOICE"})
     */
    protected $id;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string",  unique=true, nullable=true)
     * @JMS\Groups({"INVOICE"})
     */
    private $invoiceNumber;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({"INVOICE"})
     */
    private $invoiceDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": "UNPAID"})
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $documentUrl;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="InvoiceRuleTemplate", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinTable(name="invoice_invoice_rules")
     * @JMS\Type("ArrayCollection")
     * @JMS\Groups({"INVOICE"})
     */
    private $invoiceRules;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="InvoiceRuleLocked", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinTable(name="invoice_invoice_rules_locked")
     * @JMS\Type("ArrayCollection")
     * @JMS\Groups({"INVOICE"})
     */
    private $lockedRules;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Company")
     * @JMS\Groups({"INVOICE"})
     */
    private $company;


    /**
     * @var string
     * @ORM\Column(type="string", name="company_name", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $companyName;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_vat_number", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $companyVatNumber;

    /**
     * @var string
     * @ORM\Column(type="string", name="company_debtor_number", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $companyDebtorNumber;

    /**
     * @var InvoiceSenderDetails
     * @ORM\ManyToOne(targetEntity="InvoiceSenderDetails", inversedBy="invoices", cascade={"persist"})
     * @ORM\JoinColumn(name="invoice_invoice_sender_details_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\InvoiceSenderDetails")
     * @JMS\Groups({"INVOICE"})
     */
    private $senderDetails;

    /**
     * @var string
     * @ORM\Column(type="string", name="ubn", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $ubn;


    /**
     * @var bool
     * @ORM\Column(name="is_deleted", type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Groups({"INVOICE"})
     */
    private $isDeleted = false;

    /**
     * Invoice constructor.
     */
    public function __construct()
    {
        $this->invoiceRules = new ArrayCollection();
        $this->lockedRules = new ArrayCollection();
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

    public function addInvoiceRule(InvoiceRuleTemplate $invoiceRule){
        $this->invoiceRules[] = $invoiceRule;
    }

    public function removeInvoiceRule(InvoiceRule $invoiceRule){
        $this->invoiceRules->removeElement($invoiceRule);
    }

    /**
     * @return ArrayCollection
     */
    public function getLockedInvoiceRules()
    {
        return $this->lockedRules;
    }

    /**
     * @param ArrayCollection $invoiceRules
     */
    public function setLockedInvoiceRules($invoiceRules)
    {
        $this->lockedRules = $invoiceRules;
    }

    public function addLockedInvoiceRule(InvoiceRuleLocked $invoiceRule){
        $this->lockedRules[] = $invoiceRule;
    }

    public function removeLockedInvoiceRule(InvoiceRuleLocked $invoiceRule){
        $this->lockedRules->removeElement($invoiceRule);
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

    public function copyValues(Invoice $invoice){
        $this->setCompany($invoice->getCompany());
        $this->setCompanyName($invoice->getCompanyName());
        $this->setCompanyVatNumber($invoice->getCompanyVatNumber());
        $this->setCompanyDebtorNumber($invoice->getCompanyDebtorNumber());
        $this->setUbn($invoice->getUbn());
        $this->setDocumentUrl($invoice->getDocumentUrl());
        $this->setInvoiceDate($invoice->getInvoiceDate());
        $this->setInvoiceNumber($invoice->getInvoiceNumber());
        $this->setStatus($invoice->getStatus());
    }
}