<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InvoiceRuleTemplate
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRuleTemplateRepository")
 * @package AppBundle\Entity
 */
class InvoiceRuleTemplate
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $vatPercentageRate;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $priceExclVat;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $sortOrder;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $category;

    /**
     * @var string
     * @ORM\Column(type="string", name="type")
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $type;

    /**
     * @var Invoice
     *
     * @ORM\ManyToMany(targetEntity="Invoice", mappedBy="invoiceRules", cascade={"persist"})
     * @JMS\Type("array")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $invoices;

    /**
     * @var InvoiceRuleLocked
     * @ORM\OneToOne(targetEntity="InvoiceRuleLocked")
     * @ORM\JoinColumn(name="locked_id", referencedColumnName="id")
     */
    private $lockedVersion;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="is_deleted")
     * @JMS\Type("boolean")
     * @JMS\Groups({"INVOICE_RULE_TEMPLATE"})
     */
    private $isDeleted = false;

    /**
     * InvoiceRuleTemplate constructor.
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
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return int
     */
    public function getVatPercentageRate()
    {
        return $this->vatPercentageRate;
    }

    /**
     * @param int $vatPercentageRate
     */
    public function setVatPercentageRate($vatPercentageRate)
    {
        $this->vatPercentageRate = $vatPercentageRate;
    }

    /**
     * @return int
     */
    public function getPriceExclVat()
    {
        return $this->priceExclVat;
    }

    /**
     * @param int $priceExclVat
     */
    public function setPriceExclVat($priceExclVat)
    {
        $this->priceExclVat = $priceExclVat;
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
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
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
     */
    public function setCategory($category)
    {
        $this->category = $category;
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
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @param $invoices
     */
    public function setInvoices($invoices)
    {
        $this->invoices = $invoices;
    }

    public function addInvoice(Invoice $invoice){
        $this->invoices[] = $invoice;
    }

    public function removeInvoice(Invoice $invoice) {
        $this->invoices->removeElement($invoice);
    }

    /**
     * @return InvoiceRuleLocked
     */
    public function getLockedVersion()
    {
        return $this->lockedVersion;
    }

    /**
     * @param InvoiceRuleLocked $lockedVersion
     */
    public function setLockedVersion($lockedVersion)
    {
        $this->lockedVersion = $lockedVersion;
    }




    /**
     * TODO Find a better way to clone values (excluding the id) than using custom getters and setters
     * @param InvoiceRuleTemplate $invoiceRuleTemplate
     */
    public function copyValues(InvoiceRuleTemplate $invoiceRuleTemplate)
    {
        $this->setCategory($invoiceRuleTemplate->getCategory());
        $this->setDescription($invoiceRuleTemplate->getDescription());
        $this->setPriceExclVat($invoiceRuleTemplate->getPriceExclVat());
        $this->setSortOrder($invoiceRuleTemplate->getSortOrder());
        $this->setVatPercentageRate($invoiceRuleTemplate->getVatPercentageRate());
    }
}