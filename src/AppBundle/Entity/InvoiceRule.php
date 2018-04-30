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
     * @var boolean
     * @ORM\Column(type="boolean", name="is_batch", options={"default":false})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $isBatch = false;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\InvoiceRuleSelection", mappedBy="invoiceRule", cascade={"persist", "remove"}, orphanRemoval=true)
     * @JMS\Type("ArrayCollection<AppBundle\Entity\InvoiceRuleSelection>")
     * @JMS\Exclude()
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $invoiceRuleSelections;

    /**
     * InvoiceRule constructor.
     */
    public function __construct()
    {
        $this->invoiceRuleSelections = new ArrayCollection();
    }

    function __clone()
    {
     $this->id = null;
     $this->isBatch = false;
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
     * @return ArrayCollection
     */
    public function getInvoiceRuleSelections()
    {
        return $this->invoiceRuleSelections;
    }

    /**
     * @param ArrayCollection $invoiceRuleSelections
     * @return InvoiceRule
     */
    public function setInvoiceRuleSelections($invoiceRuleSelections)
    {
        $this->invoiceRuleSelections = $invoiceRuleSelections;
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

    /**
     * @return bool
     */
    public function isBatch()
    {
        return $this->isBatch;
    }

    /**
     * @param bool $isBatch
     */
    public function setIsBatch($isBatch)
    {
        $this->isBatch = $isBatch;
    }
}