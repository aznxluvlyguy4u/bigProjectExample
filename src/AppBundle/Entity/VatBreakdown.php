<?php


namespace AppBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as JMS;

class VatBreakdown
{
    /**
     * @var float
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "INVOICE_OVERVIEW"
     * })
     */
    private $totalExclVat;

    /**
     * @var float
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $vat;

    /**
     * @var float
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY",
     *     "INVOICE_OVERVIEW"
     * })
     */
    private $totalInclVat;

    /**
     * @var ArrayCollection|VatBreakdownRecord[]
     * @JMS\Type("ArrayCollection<AppBundle\Entity\VatBreakdownRecord>")
     * @JMS\Groups({
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $records;

    /**
     * VatBreakdown constructor.
     */
    public function __construct()
    {
        $this->initializeRecords();
    }


    private function initializeRecords()
    {
        if (!$this->records) {
            $this->records = new ArrayCollection();
        }
    }

    /**
     * @return float
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * @param float $vat
     * @return VatBreakdown
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalExclVat()
    {
        return $this->totalExclVat;
    }

    /**
     * @param float $totalExclVat
     * @return VatBreakdown
     */
    public function setTotalExclVat($totalExclVat)
    {
        $this->totalExclVat = $totalExclVat;
        return $this;
    }

    /**
     * @return float
     */
    public function getTotalInclVat()
    {
        return $this->totalInclVat;
    }

    /**
     * @param float $totalInclVat
     * @return VatBreakdown
     */
    public function setTotalInclVat($totalInclVat)
    {
        $this->totalInclVat = $totalInclVat;
        return $this;
    }

    /**
     * @return VatBreakdownRecord[]|ArrayCollection
     */
    public function getRecords()
    {
        $this->initializeRecords();
        return $this->records;
    }

    /**
     * @param VatBreakdownRecord[]|ArrayCollection $records
     * @return VatBreakdown
     */
    public function setRecords($records)
    {
        $this->records = $records;
        return $this;
    }


    /**
     * @param VatBreakdownRecord $record
     * @return VatBreakdown
     */
    public function addRecord($record)
    {
        $this->initializeRecords();
        $this->records->add($record);
        return $this;
    }


    /**
     * @param VatBreakdownRecord $record
     * @return VatBreakdown
     */
    public function removeRecord($record)
    {
        $this->initializeRecords();
        $this->records->removeElement($record);
        return $this;
    }

}