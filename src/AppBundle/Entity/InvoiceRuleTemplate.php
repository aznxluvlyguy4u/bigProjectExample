<?php

namespace AppBundle\Entity;

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
     * @JMS\Groups({"INVOICE"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     * @JMS\Groups({"INVOICE"})
     */
    private $vatPercentageRate;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     * @JMS\Type("float")
     * @JMS\Groups({"INVOICE"})
     */
    private $priceExclVat;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @JMS\Groups({"INVOICE"})
     */
    private $sortOrder;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({"INVOICE"})
     */
    private $category;

    /**
     * InvoiceRuleTemplate constructor.
     */
    public function __construct()
    {
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
}