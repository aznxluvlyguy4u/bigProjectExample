<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\RequestStateType;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareTagReplace
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareTagReplaceResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareTagReplaceResponse extends DeclareBaseResponse {

    /**
     * @var DeclareTagReplace
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareTagReplace", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareTagReplace")
     */
    private $declareTagReplaceRequestMessage;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ulnCountryCodeToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ulnNumberToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ulnNumberReplacement;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $ulnCountryCodeReplacement;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $replaceDate;

  /**
   * DeclareTagReplaceResponse constructor.
   */
    public function __construct() {
      $this->setLogDate(new \DateTime());
    }

    /**
     * Set ulnCountryCodeToReplace
     *
     * @param string $ulnCountryCodeToReplace
     *
     * @return DeclareTagReplaceResponse
     */
    public function setUlnCountryCodeToReplace($ulnCountryCodeToReplace)
    {
        $this->ulnCountryCodeToReplace = $ulnCountryCodeToReplace;

        return $this;
    }

    /**
     * Get ulnCountryCodeToReplace
     *
     * @return string
     */
    public function getUlnCountryCodeToReplace()
    {
        return $this->ulnCountryCodeToReplace;
    }

    /**
     * Set ulnNumberToReplace
     *
     * @param string $ulnNumberToReplace
     *
     * @return DeclareTagReplaceResponse
     */
    public function setUlnNumberToReplace($ulnNumberToReplace)
    {
        $this->ulnNumberToReplace = $ulnNumberToReplace;

        return $this;
    }

    /**
     * Get ulnNumberToReplace
     *
     * @return string
     */
    public function getUlnNumberToReplace()
    {
        return $this->ulnNumberToReplace;
    }

    /**
     * Set ulnNumberReplacement
     *
     * @param string $ulnNumberReplacement
     *
     * @return DeclareTagReplaceResponse
     */
    public function setUlnNumberReplacement($ulnNumberReplacement)
    {
        $this->ulnNumberReplacement = $ulnNumberReplacement;

        return $this;
    }

    /**
     * Get ulnNumberReplacement
     *
     * @return string
     */
    public function getUlnNumberReplacement()
    {
        return $this->ulnNumberReplacement;
    }

    /**
     * Set ulnCountryCodeReplacement
     *
     * @param string $ulnCountryCodeReplacement
     *
     * @return DeclareTagReplaceResponse
     */
    public function setUlnCountryCodeReplacement($ulnCountryCodeReplacement)
    {
        $this->ulnCountryCodeReplacement = $ulnCountryCodeReplacement;

        return $this;
    }

    /**
     * Get ulnCountryCodeReplacement
     *
     * @return string
     */
    public function getUlnCountryCodeReplacement()
    {
        return $this->ulnCountryCodeReplacement;
    }

    /**
     * Set replaceDate
     *
     * @param \DateTime $replaceDate
     *
     * @return DeclareTagReplaceResponse
     */
    public function setReplaceDate($replaceDate)
    {
        $this->replaceDate = $replaceDate;

        return $this;
    }

    /**
     * Get replaceDate
     *
     * @return \DateTime
     */
    public function getReplaceDate()
    {
        return $this->replaceDate;
    }

    /**
     * Set declareTagReplaceRequestMessage
     *
     * @param \AppBundle\Entity\DeclareTagReplace $declareTagReplaceRequestMessage
     *
     * @return DeclareTagReplaceResponse
     */
    public function setDeclareTagReplaceRequestMessage(\AppBundle\Entity\DeclareTagReplace $declareTagReplaceRequestMessage = null)
    {
        $this->declareTagReplaceRequestMessage = $declareTagReplaceRequestMessage;

        return $this;
    }

    /**
     * Get declareTagReplaceRequestMessage
     *
     * @return \AppBundle\Entity\DeclareTagReplace
     */
    public function getDeclareTagReplaceRequestMessage()
    {
        return $this->declareTagReplaceRequestMessage;
    }
}
