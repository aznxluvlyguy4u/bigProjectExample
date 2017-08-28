<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InvoiceRules
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InvoiceRuleTemplateRepository")
 * @package AppBundle\Entity
 */
class InvoiceRuleTemplate
{
    use EntityClassInfo;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({"INVOICE_RULE"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE_RULE"})
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({"INVOICE_RULE"})
     */
    private $vatPercentageRate;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({"INVOICE_RULE"})
     */
    private $priceExclVat;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE_RULE"})
     */
    private $category;

    /**
     * InvoiceRuleTemplate constructor.
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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

    public function copyValues(InvoiceRuleTemplate $invoiceRuleTemplate) {
        $this->setDescription($invoiceRuleTemplate->getDescription());
        $this->setPriceExclVat($invoiceRuleTemplate->getPriceExclVat());
        $this->setVatPercentageRate($invoiceRuleTemplate->getVatPercentageRate());
        $this->setCategory($invoiceRuleTemplate->getCategory());
    }
}