<?php


namespace AppBundle\Entity;


use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InvoiceRule
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRuleSelectionRepository")
 * @package AppBundle\Entity
 */
class InvoiceRuleSelection
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
    private $id;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Invoice", inversedBy="invoiceRuleSelections", cascade={"persist"})
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\Invoice")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $invoice;

    /**
     * @var InvoiceRule
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\InvoiceRule", inversedBy="invoiceRuleSelections")
     * @ORM\JoinColumn(name="invoice_rule_id", referencedColumnName="id", onDelete="set null")
     * @JMS\Type("AppBundle\Entity\InvoiceRule")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $invoiceRule;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default":0})
     * @JMS\Type("integer")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $amount;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return InvoiceRuleSelection
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return Invoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param Invoice $invoice
     * @return InvoiceRuleSelection
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
        return $this;
    }

    /**
     * @return InvoiceRule
     */
    public function getInvoiceRule()
    {
        return $this->invoiceRule;
    }

    /**
     * @param InvoiceRule $invoiceRule
     * @return InvoiceRuleSelection
     */
    public function setInvoiceRule($invoiceRule)
    {
        $this->invoiceRule = $invoiceRule;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return InvoiceRuleSelection
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }


}