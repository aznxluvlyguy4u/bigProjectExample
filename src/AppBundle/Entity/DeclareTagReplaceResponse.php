<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareTagReplace
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareTagReplaceResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareTagReplaceResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

    /**
     * @var DeclareTagReplace
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareTagReplace", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareTagReplace")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $declareTagReplaceRequestMessage;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnCountryCodeToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnNumberToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnNumberReplacement;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $ulnCountryCodeReplacement;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     */
    private $replaceDate;

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


    /**
     * @param DeclareTagReplace $tagReplace
     * @return DeclareTagReplaceResponse
     */
    public function setDeclareTagReplaceIncludingAllValues(DeclareTagReplace $tagReplace): DeclareTagReplaceResponse
    {
        $this->setDeclareBaseValues($tagReplace);
        $this->setDeclareTagReplaceRequestMessage($tagReplace);
        $this->setReplaceDate($tagReplace->getReplaceDate());
        $this->setUlnCountryCodeToReplace($tagReplace->getUlnCountryCodeToReplace());
        $this->setUlnNumberToReplace($tagReplace->getUlnNumberToReplace());
        $this->setUlnCountryCodeReplacement($tagReplace->getUlnCountryCodeReplacement());
        $this->setUlnNumberReplacement($tagReplace->getUlnNumberReplacement());
        return $this;
    }
}
