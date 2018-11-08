<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Invoice
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LedgerCategoryRepository")
 * @package AppBundle\Entity
 */
class LedgerCategory
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
     *     "INVOICE_NO_COMPANY",
     *     "BASIC"
     * })
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="code", nullable=false, unique=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "BASIC"
     * })
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="description", nullable=false)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "BASIC"
     * })
     */
    private $description;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", name="is_active", options={"default":true}, nullable=false)
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "BASIC"
     * })
     */
    private $isActive;

    /**
     * @var ArrayCollection<InvoiceRule>
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\InvoiceRule", mappedBy="ledgerCategory")
     * @JMS\Type("ArrayCollection<AppBundle\Entity\InvoiceRule>")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     */
    private $invoiceRules;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "DETAILS",
     * })
     */
    private $logDate;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="creation_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     * @JMS\Groups({
     *     "DETAILS",
     * })
     */
    private $creationBy;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="edited_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     * @JMS\Groups({
     *     "DETAILS",
     * })
     */
    private $editedBy;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="Employee")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     * @JMS\Groups({
     *     "DETAILS",
     * })
     */
    private $deletedBy;

    public function __construct()
    {
        $this->isActive = true;
        $this->invoiceRules = new ArrayCollection();
        $this->logDate = new \DateTime();
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
     * @return LedgerCategory
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return LedgerCategory
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
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
     * @return LedgerCategory
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return LedgerCategory
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getInvoiceRules()
    {
        if ($this->invoiceRules === null) {
            $this->invoiceRules = new ArrayCollection();
        }

        return $this->invoiceRules;
    }

    /**
     * @param ArrayCollection $invoiceRules
     * @return LedgerCategory
     */
    public function setInvoiceRules($invoiceRules)
    {
        $this->invoiceRules = $invoiceRules;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param DateTime $logDate
     * @return LedgerCategory
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return Employee
     */
    public function getCreationBy()
    {
        return $this->creationBy;
    }

    /**
     * @param Employee $creationBy
     * @return LedgerCategory
     */
    public function setCreationBy($creationBy)
    {
        $this->creationBy = $creationBy;
        return $this;
    }

    /**
     * @return Employee
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * @param Employee $editedBy
     * @return LedgerCategory
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
        return $this;
    }

    /**
     * @return Employee
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @param Employee $deletedBy
     * @return LedgerCategory
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }


}