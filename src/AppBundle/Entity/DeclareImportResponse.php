<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareImportResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareImportResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareImportResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

  /**
   * @var DeclareImport
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareImport", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareImport")
   * @JMS\Groups({
   *     "RESPONSE_PERSISTENCE"
   * })
   */
  private $declareImportRequestMessage;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $importDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $animalCountryOrigin;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $gender;

    /**
     * Set declareImportRequestMessage
     *
     * @param \AppBundle\Entity\DeclareImport $declareImportRequestMessage
     *
     * @return DeclareImportResponse
     */
    public function setDeclareImportRequestMessage(\AppBundle\Entity\DeclareImport $declareImportRequestMessage = null)
    {
        $this->declareImportRequestMessage = $declareImportRequestMessage;

        return $this;
    }

    /**
     * Get declareImportRequestMessage
     *
     * @return \AppBundle\Entity\DeclareImport
     */
    public function getDeclareImportRequestMessage()
    {
        return $this->declareImportRequestMessage;
    }

    /**
     * @return \DateTime
     */
    public function getImportDate()
    {
        return $this->importDate;
    }

    /**
     * @param \DateTime $importDate
     */
    public function setImportDate($importDate)
    {
        $this->importDate = $importDate;
    }

    /**
     * @return string
     */
    public function getAnimalCountryOrigin()
    {
        return $this->animalCountryOrigin;
    }

    /**
     * @param string $animalCountryOrigin
     */
    public function setAnimalCountryOrigin($animalCountryOrigin)
    {
        $this->animalCountryOrigin = $animalCountryOrigin;
    }




    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return DeclareImportResponse
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param DeclareImport $import
     * @return DeclareImportResponse
     */
    public function setDeclareImportIncludingAllValues(DeclareImport $import): DeclareImportResponse
    {
        $this->setDeclareBaseValues($import);
        $this->setDeclareImportRequestMessage($import);
        $this->setImportDate($import->getImportDate());
        $this->setAnimalCountryOrigin($import->getAnimalCountryOrigin());
        return $this;
    }

}
