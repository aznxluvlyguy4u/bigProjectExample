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
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareTagReplaceRepository")
 * @package AppBundle\Entity
 */
class DeclareTagReplace extends DeclareBase {

    /**
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="tagReplacements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnNumberToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnCountryCodeToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $animalOrderNumberToReplace;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnNumberReplacement;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $ulnCountryCodeReplacement;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=false)
     */
    private $animalOrderNumberReplacement;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $animalType;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $replaceDate;

    /**
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="tagTransfers", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     */
    private $location;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="DeclareTagReplaceResponse", mappedBy="declareTagReplaceRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_tag_replace_request_message_id", referencedColumnName="id")
     * @ORM\OrderBy({"logDate" = "ASC"})
     * @JMS\Type("array")
     */
    private $responses;

    /**
     * @ORM\OneToOne(targetEntity="RevokeDeclaration", inversedBy="tagReplace", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_id", referencedColumnName="id", nullable=true)
     * @JMS\Type("AppBundle\Entity\RevokeDeclaration")
     */
    private $revoke;

    /**
     * DeclareTagReplace constructor.
     */
    public function  __construct() {
      parent::__construct();

      $this->setRequestState(RequestStateType::OPEN);
      $this->responses = new ArrayCollection();
      $this->setLogDate(new \DateTime());
    }

    /**
     * Set ulnNumberToReplace
     *
     * @param string $ulnNumberToReplace
     *
     * @return DeclareTagReplace
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
     * Set ulnCountryCodeToReplace
     *
     * @param string $ulnCountryCodeToReplace
     *
     * @return DeclareTagReplace
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
     * Set ulnNumberReplacement
     *
     * @param string $ulnNumberReplacement
     *
     * @return DeclareTagReplace
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
     * @return DeclareTagReplace
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
     * Set animalType
     *
     * @param integer $animalType
     *
     * @return DeclareTagReplace
     */
    public function setAnimalType($animalType)
    {
        $this->animalType = $animalType;

        return $this;
    }

    /**
     * Get animalType
     *
     * @return integer
     */
    public function getAnimalType()
    {
        return $this->animalType;
    }

    /**
     * Set replaceDate
     *
     * @param \DateTime $replaceDate
     *
     * @return DeclareTagReplace
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
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return DeclareTagReplace
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;
        $this->setUbn($location->getUbn());

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
     * @param \AppBundle\Entity\DeclareTagReplaceResponse $response
     *
     * @return DeclareTagReplace
     */
    public function addResponse(\AppBundle\Entity\DeclareTagReplaceResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareTagReplaceResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareTagReplaceResponse $response)
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
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareTagReplace
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

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
     * Set revoke
     *
     * @param \AppBundle\Entity\RevokeDeclaration $revoke
     *
     * @return DeclareTagReplace
     */
    public function setRevoke(\AppBundle\Entity\RevokeDeclaration $revoke = null)
    {
        $this->revoke = $revoke;

        return $this;
    }

    /**
     * Get revoke
     *
     * @return \AppBundle\Entity\RevokeDeclaration
     */
    public function getRevoke()
    {
        return $this->revoke;
    }

    /**
     * Set animalOrderNumberToReplace
     *
     * @param string $animalOrderNumberToReplace
     *
     * @return DeclareTagReplace
     */
    public function setAnimalOrderNumberToReplace($animalOrderNumberToReplace)
    {
        $this->animalOrderNumberToReplace = $animalOrderNumberToReplace;

        return $this;
    }

    /**
     * Get animalOrderNumberToReplace
     *
     * @return string
     */
    public function getAnimalOrderNumberToReplace()
    {
        return $this->animalOrderNumberToReplace;
    }

    /**
     * Set animalOrderNumberReplacement
     *
     * @param string $animalOrderNumberReplacement
     *
     * @return DeclareTagReplace
     */
    public function setAnimalOrderNumberReplacement($animalOrderNumberReplacement)
    {
        $this->animalOrderNumberReplacement = $animalOrderNumberReplacement;

        return $this;
    }

    /**
     * Get animalOrderNumberReplacement
     *
     * @return string
     */
    public function getAnimalOrderNumberReplacement()
    {
        return $this->animalOrderNumberReplacement;
    }
}
