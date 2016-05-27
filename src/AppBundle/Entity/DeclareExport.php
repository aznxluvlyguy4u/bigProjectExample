<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class DeclareExport
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareExportRepository")
 * @package AppBundle\Entity
 */
class DeclareExport extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="exports")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     * @Expose
     */
    private $ulnNumber;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $pedigreeCountryCode;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     */
    private $pedigreeNumber;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=true)
     * @JMS\Type("boolean")
     * @Expose
     */
    private $isExportAnimal;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     * @Expose
     */
    private $animalType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @Expose
     */
    private $animalObjectType;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @Expose
     */
    private $exportDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    private $reasonOfExport;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="exports", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;


    /**
     * @ORM\OneToMany(targetEntity="DeclareExportResponse", mappedBy="declareExportRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_export_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="export", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     * @Expose
     */
    private $revoke;

    /**
     * Set exportDate
     *
     * @param \DateTime $exportDate
     *
     * @return DeclareExport
     */
    public function setExportDate($exportDate)
    {
        $this->exportDate = $exportDate;

        return $this;
    }

    /**
     * Get exportDate
     *
     * @return \DateTime
     */
    public function getExportDate()
    {
        return $this->exportDate;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareExport
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        if($animal != null) {

            if($animal->getUlnCountryCode()!=null && $animal->getUlnNumber()!=null) {
                $this->ulnCountryCode = $animal->getUlnCountryCode();
                $this->ulnNumber = $animal->getUlnNumber();
            }

            if ($animal->getPedigreeCountryCode()!=null && $animal->getPedigreeNumber()!=null){
                $this->pedigreeCountryCode = $animal->getPedigreeCountryCode();
                $this->pedigreeNumber = $animal->getPedigreeNumber();
            }
        }

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareExport
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;
        $this->ubn = $location->getUbn();

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareExportResponse $response
     *
     * @return DeclareExport
     */
    public function addResponse(\AppBundle\Entity\DeclareExportResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareExportResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareExportResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * @return RevokeDeclaration
     */
    public function getRevoke()
    {
        return $this->revoke;
    }

    /**
     * @param RevokeDeclaration $revoke
     */
    public function setRevoke($revoke = null)
    {
        $this->revoke = $revoke;
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
     * @return int
     */
    public function getAnimalType()
    {
        return $this->animalType;
    }

    /**
     * @param int $animalType
     */
    public function setAnimalType($animalType)
    {
        $this->animalType = $animalType;
    }

    /**
     * @return string
     */
    public function getAnimalObjectType()
    {
        return $this->animalObjectType;
    }

    /**
     * @param string $animalObjectType
     */
    public function setAnimalObjectType($animalObjectType)
    {
        $this->animalObjectType = $animalObjectType;
    }

    /**
     * @return string
     */
    public function getReasonOfExport()
    {
        return $this->reasonOfExport;
    }

    /**
     * @param string $reasonOfExport
     */
    public function setReasonOfExport($reasonOfExport)
    {
        $this->reasonOfExport = $reasonOfExport;
    }



}
