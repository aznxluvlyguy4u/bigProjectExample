<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InvoiceRule
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRuleRepository")
 * @package AppBundle\Entity
 */
class InvoiceRule
{
    use EntityClassInfo;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "INVOICE_RULE",
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
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $vatPercentageRate;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $priceExclVat;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $sortOrder;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default":"GENERAL"})
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $category;

    /**
     * @var string
     * @ORM\Column(type="string", name="type", options={"default":"custom"})
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $type;

    /**
     * @var Invoice
     *
     * @ORM\ManyToMany(targetEntity="Invoice", inversedBy="invoiceRules", cascade={"persist"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\Invoice>")
     */
    private $invoices;

    /**
     * @var LedgerCategory
     * @ORM\ManyToOne(targetEntity="LedgerCategory", inversedBy="invoiceRules")
     * @JMS\Type("AppBundle\Entity\LedgerCategory")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $ledgerCategory;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="is_deleted", options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $isDeleted = false;

    /**
     * InvoiceRule constructor.
     */
    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return InvoiceRule
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return float
     */
    public function getVatPercentageRate()
    {
        return $this->vatPercentageRate;
    }

    /**
     * @param float $vatPercentageRate
     * @return InvoiceRule
     */
    public function setVatPercentageRate($vatPercentageRate)
    {
        $this->vatPercentageRate = $vatPercentageRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getPriceExclVat()
    {
        return $this->priceExclVat;
    }

    /**
     * @param float $priceExclVat
     * @return InvoiceRule
     */
    public function setPriceExclVat($priceExclVat)
    {
        $this->priceExclVat = $priceExclVat;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return InvoiceRule
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return InvoiceRule
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return InvoiceRule
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
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
     * @return Invoice
     */
    public function getInvoices()
    {
        return $this->invoices;
    }

    /**
     * @param ArrayCollection $invoices
     * @return InvoiceRule
     */
    public function setInvoices($invoices)
    {
        $this->invoices = $invoices;
        return $this;
    }

    /**
     * @param Invoice $invoice
     */
    public function addInvoice(Invoice $invoice) {
        $this->invoices->add($invoice);
    }

    /**
     * @param Invoice $invoice
     */
    public function removeInvoice(Invoice $invoice) {
        $this->invoices->removeElement($invoice);
    }

    /**
     * @return LedgerCategory
     */
    public function getLedgerCategory()
    {
        return $this->ledgerCategory;
    }

    /**
     * @param LedgerCategory $ledgerCategory
     * @return InvoiceRule
     */
    public function setLedgerCategory($ledgerCategory)
    {
        $this->ledgerCategory = $ledgerCategory;
        return $this;
    }

    /**
     * TODO Find a better way to clone values (excluding the id) than using custom getters and setters
     * @param InvoiceRule $invoiceRuleTemplate
     */
    public function copyValues(InvoiceRule $invoiceRuleTemplate)
    {
        $this->setDescription($invoiceRuleTemplate->getDescription());
        $this->setPriceExclVat($invoiceRuleTemplate->getPriceExclVat());
        $this->setVatPercentageRate($invoiceRuleTemplate->getVatPercentageRate());
    }


}