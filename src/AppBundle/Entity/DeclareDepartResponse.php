<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareDepartResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareDepartResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareDepartResponse extends DeclareBaseResponse
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
    private $departDate;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\Length(max = 10)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ubnNewOwner;

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
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $isDepartedAnimal;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $reasonOfDepart;

    /**
     * @var DeclareDepart
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareDepart", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareDepart")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $declareDepartRequestMessage;

    /**
     * Set declareDepartRequestMessage
     *
     * @param \AppBundle\Entity\DeclareDepart $declareDepartRequestMessage
     *
     * @return DeclareDepartResponse
     */
    public function setDeclareDepartRequestMessage(\AppBundle\Entity\DeclareDepart $declareDepartRequestMessage = null)
    {
        $this->declareDepartRequestMessage = $declareDepartRequestMessage;

        return $this;
    }

    /**
     * Get declareDepartRequestMessage
     *
     * @return \AppBundle\Entity\DeclareDepart
     */
    public function getDeclareDepartRequestMessage()
    {
        return $this->declareDepartRequestMessage;
    }


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return DeclareDepartResponse
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
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
    public function getDepartDate()
    {
        return $this->departDate;
    }

    /**
     * @param \DateTime $departDate
     */
    public function setDepartDate($departDate)
    {
        $this->departDate = $departDate;
    }

    /**
     * @return string
     */
    public function getUbnNewOwner()
    {
        return $this->ubnNewOwner;
    }

    /**
     * @param string $ubnNewOwner
     */
    public function setUbnNewOwner($ubnNewOwner)
    {
        $this->ubnNewOwner = $ubnNewOwner;
    }

    /**
     * @return boolean
     */
    public function getIsExportAnimal()
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
     * Set isDepartedAnimal
     *
     * @param boolean $isDepartedAnimal
     *
     * @return DeclareDepartResponse
     */
    public function setIsDepartedAnimal($isDepartedAnimal)
    {
        $this->isDepartedAnimal = $isDepartedAnimal;

        return $this;
    }

    /**
     * Get isDepartedAnimal
     *
     * @return boolean
     */
    public function getIsDepartedAnimal()
    {
        return $this->isDepartedAnimal;
    }

    /**
     * Set reasonOfDeparture
     *
     * @param string $reasonOfDepart
     *
     * @return DeclareDepartResponse
     */
    public function setReasonOfDepart($reasonOfDepart)
    {
        $this->reasonOfDepart = $reasonOfDepart;

        return $this;
    }

    /**
     * Get reasonOfDeparture
     *
     * @return string
     */
    public function getReasonOfDepart()
    {
        return $this->reasonOfDepart;
    }


    /**
     * @param DeclareDepart $depart
     * @return DeclareDepartResponse
     */
    public function setDeclareDepartIncludingAllValues(DeclareDepart $depart): DeclareDepartResponse
    {
        $this->setDeclareBaseValues($depart);
        $this->setDeclareDepartRequestMessage($depart);
        $this->setUlnCountryCode($depart->getUlnCountryCode());
        $this->setUlnNumber($depart->getUlnNumber());
        $this->setDepartDate($depart->getDepartDate());
        $this->setReasonOfDepart($depart->getReasonOfDepart());
        $this->setUbnNewOwner($depart->getUbnNewOwner());
        return $this;
    }
}
