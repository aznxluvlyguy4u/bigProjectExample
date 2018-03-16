<?php


namespace AppBundle\Entity;

use JMS\Serializer\Annotation as JMS;

class VatBreakdownRecord
{
    /**
     * @var float
     *
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
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $priceExclVatTotal;

    /**
     * @var float
     *
     * @JMS\Type("float")
     * @JMS\Groups({
     *     "INVOICE_RULE",
     *     "INVOICE",
     *     "INVOICE_NO_COMPANY"
     * })
     */
    private $vat;

    /**
     * @return float
     */
    public function getVatPercentageRate()
    {
        return $this->vatPercentageRate;
    }

    /**
     * @param float $vatPercentageRate
     * @return VatBreakdownRecord
     */
    public function setVatPercentageRate($vatPercentageRate)
    {
        $this->vatPercentageRate = $vatPercentageRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getPriceExclVatTotal()
    {
        return $this->priceExclVatTotal ? $this->priceExclVatTotal : 0;
    }

    /**
     * @param float $priceExclVatTotal
     * @return VatBreakdownRecord
     */
    public function setPriceExclVatTotal($priceExclVatTotal)
    {
        $this->priceExclVatTotal = $priceExclVatTotal;
        return $this;
    }


    /**
     * @param float $priceExclVat
     * @return VatBreakdownRecord
     */
    public function addToPriceExclVatTotal($priceExclVat)
    {
        $this->priceExclVatTotal = $this->getPriceExclVatTotal() + $priceExclVat;
        return $this;
    }

    /**
     * @return float
     */
    public function getVat()
    {
        return $this->vat ? $this->vat : 0;
    }

    /**
     * @param float $vat
     * @return VatBreakdownRecord
     */
    public function setVat($vat)
    {
        $this->vat = $vat;
        return $this;
    }


    /**
     * @param float $vat
     * @return VatBreakdownRecord
     */
    public function addToVat($vat)
    {
        $this->vat = $this->getVat() + $vat;
        return $this;
    }
}