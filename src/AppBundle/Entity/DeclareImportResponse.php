<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclareImportResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareImportResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareImportResponse extends DeclareBaseResponse {

  /**
   * @var DeclareImport
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareImport", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareImport")
   */
  private $declareImportRequestMessage;

    /**
     * 2016-04-01T22:00:48.131Z
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $importDate;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $animalCountryOrigin;

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



}
