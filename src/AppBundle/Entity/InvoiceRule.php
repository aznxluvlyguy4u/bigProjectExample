<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InvoiceRules
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRuleRepository")
 * @package AppBundle\Entity
 */
class InvoiceRule {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $vatPercentageRate;

    /**
     * @var string
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $priceExclVat;

    /**
     * @var Invoice
     *
     * @ORM\ManyToOne(targetEntity="Invoice", inversedBy="invoiceRules", cascade={"persist"})
     * @JMS\Type("array")
     */
    private $invoice;

    /**
     * InvoiceRule constructor.
     */
    public function __construct()
    {
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
     * @return string
     */
    public function getVatPercentageRate()
    {
        return $this->vatPercentageRate;
    }

    /**
     * @param string $vatPercentageRate
     */
    public function setVatPercentageRate($vatPercentageRate)
    {
        $this->vatPercentageRate = $vatPercentageRate;
    }

    /**
     * @return string
     */
    public function getPriceExclVat()
    {
        return $this->priceExclVat;
    }

    /**
     * @param string $priceExclVat
     */
    public function setPriceExclVat($priceExclVat)
    {
        $this->priceExclVat = $priceExclVat;
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
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }
}