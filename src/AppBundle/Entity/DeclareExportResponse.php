<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareExportResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareExportResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareExportResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $pedigreeCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $pedigreeNumber;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $exportDate;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $isExportAnimal;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $reasonOfExport;

    /**
     * @var DeclareExport
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareExport", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareExport")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $declareExportRequestMessage;

    /**
     * Set declareExportRequestMessage
     *
     * @param \AppBundle\Entity\DeclareExport $declareExportRequestMessage
     *
     * @return DeclareExportResponse
     */
    public function setDeclareExportRequestMessage(\AppBundle\Entity\DeclareExport $declareExportRequestMessage = null)
    {
        $this->declareExportRequestMessage = $declareExportRequestMessage;

        return $this;
    }

    /**
     * Get declareExportRequestMessage
     *
     * @return \AppBundle\Entity\DeclareExport
     */
    public function getDeclareExportRequestMessage()
    {
        return $this->declareExportRequestMessage;
    }

    /**
     * @return string
     */
    public function getUlnCountryCode()
    {
        return $this->ulnCountryCode;
    }

    /**
     * @param string $ulnCountryCode
     */
    public function setUlnCountryCode($ulnCountryCode)
    {
        $this->ulnCountryCode = $ulnCountryCode;
    }

    /**
     * @return string
     */
    public function getUlnNumber()
    {
        return $this->ulnNumber;
    }

    /**
     * @param string $ulnNumber
     */
    public function setUlnNumber($ulnNumber)
    {
        $this->ulnNumber = $ulnNumber;
    }

    /**
     * @return string
     */
    public function getPedigreeCountryCode()
    {
        return $this->pedigreeCountryCode;
    }

    /**
     * @param string $pedigreeCountryCode
     */
    public function setPedigreeCountryCode($pedigreeCountryCode)
    {
        $this->pedigreeCountryCode = $pedigreeCountryCode;
    }

    /**
     * @return string
     */
    public function getPedigreeNumber()
    {
        return $this->pedigreeNumber;
    }

    /**
     * @param string $pedigreeNumber
     */
    public function setPedigreeNumber($pedigreeNumber)
    {
        $this->pedigreeNumber = $pedigreeNumber;
    }

    /**
     * @return \DateTime
     */
    public function getExportDate()
    {
        return $this->exportDate;
    }

    /**
     * @param \DateTime $exportDate
     */
    public function setExportDate($exportDate)
    {
        $this->exportDate = $exportDate;
    }



    /**
     * @return boolean
     */
    public function isIsExportAnimal()
    {
        return $this->isExportAnimal;
    }

    /**
     * @param boolean $isExportAnimal
     */
    public function setIsExportAnimal($isExportAnimal)
    {
        $this->isExportAnimal = $isExportAnimal;
    }



    /**
     * Get isExportAnimal
     *
     * @return boolean
     */
    public function getIsExportAnimal()
    {
        return $this->isExportAnimal;
    }

    /**
     * Set reasonOfExport
     *
     * @param string $reasonOfExport
     *
     * @return DeclareExportResponse
     */
    public function setReasonOfExport($reasonOfExport)
    {
        $this->reasonOfExport = $reasonOfExport;

        return $this;
    }

    /**
     * Get reasonOfExport
     *
     * @return string
     */
    public function getReasonOfExport()
    {
        return $this->reasonOfExport;
    }


    /**
     * @param DeclareExport $export
     * @return DeclareExportResponse
     */
    public function setDeclareExportIncludingAllValues(DeclareExport $export): DeclareExportResponse
    {
        $this->setDeclareBaseValues($export);
        $this->setDeclareExportRequestMessage($export);
        $this->setUlnCountryCode($export->getUlnCountryCode());
        $this->setUlnNumber($export->getUlnNumber());
        $this->setExportDate($export->getExportDate());
        $this->setReasonOfExport($export->getReasonOfExport());
        return $this;
    }
}
