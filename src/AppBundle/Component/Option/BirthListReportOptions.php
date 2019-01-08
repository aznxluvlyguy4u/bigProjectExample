<?php


namespace AppBundle\Component\Option;

use JMS\Serializer\Annotation as JMS;

class BirthListReportOptions
{
    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $language;

    /**
     * @var string|null
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $breedCode;

    /**
     * @var string|null
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $pedigreeRegisterAbbreviation;

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return BirthListReportOptions
     */
    public function setLanguage(string $language): BirthListReportOptions
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBreedCode(): ?string
    {
        return $this->breedCode;
    }

    /**
     * @param string|null $breedCode
     * @return BirthListReportOptions
     */
    public function setBreedCode(?string $breedCode): BirthListReportOptions
    {
        $this->breedCode = $breedCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPedigreeRegisterAbbreviation(): ?string
    {
        return $this->pedigreeRegisterAbbreviation;
    }

    /**
     * @param string|null $pedigreeRegisterAbbreviation
     * @return BirthListReportOptions
     */
    public function setPedigreeRegisterAbbreviation(?string $pedigreeRegisterAbbreviation): BirthListReportOptions
    {
        $this->pedigreeRegisterAbbreviation = $pedigreeRegisterAbbreviation;
        return $this;
    }



}