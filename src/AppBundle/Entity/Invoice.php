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
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string",  unique=true)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $invoiceNumber;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $invoiceDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": "UNPAID"})
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $documentUrl;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="InvoiceRule", mappedBy="invoice")
     * @JMS\Type("array")
     */
    private $invoiceRules;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="invoices", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Company")
     */
    private $company;

    /**
     * Invoice constructor.
     */
    public function __construct()
    {
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
}