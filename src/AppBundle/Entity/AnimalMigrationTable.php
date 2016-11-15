<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;


/**
 * Class AnimalMigrationTable
 * @ORM\Table(name="animal_migration_table",indexes={@ORM\Index(name="migration_idx", columns={"vsm_id", "animal_id"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\CountryRepository")
 * @package AppBundle\Entity
 */
class AnimalMigrationTable
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $vsmId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $animalId;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $ulnOrigin;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $stnOrigin;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $ulnCountryCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $ulnNumber;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $animalOrderNumber;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $pedigreeCountryCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $pedigreeNumber;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $nickName;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $fatherVsmId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $fatherId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $motherVsmId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $motherId;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $genderInFile;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $genderInDatabase;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $dateOfBirth;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $breedCode;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $oldBreedCode;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isBreedCodeUpdated;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $ubnOfBirth;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $locationOfBirthId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeRegisterId;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $BreedType;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $scrapieGenotype;

    /**
     * AnimalMigrationTable constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getVsmId()
    {
        return $this->vsmId;
    }

    /**
     * @param int $vsmId
     */
    public function setVsmId($vsmId)
    {
        $this->vsmId = $vsmId;
    }

    /**
     * @return int
     */
    public function getAnimalId()
    {
        return $this->animalId;
    }

    /**
     * @param int $animalId
     */
    public function setAnimalId($animalId)
    {
        $this->animalId = $animalId;
    }

    /**
     * @return string
     */
    public function getUlnOrigin()
    {
        return $this->ulnOrigin;
    }

    /**
     * @param string $ulnOrigin
     */
    public function setUlnOrigin($ulnOrigin)
    {
        $this->ulnOrigin = $ulnOrigin;
    }

    /**
     * @return string
     */
    public function getStnOrigin()
    {
        return $this->stnOrigin;
    }

    /**
     * @param string $stnOrigin
     */
    public function setStnOrigin($stnOrigin)
    {
        $this->stnOrigin = $stnOrigin;
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
    public function getAnimalOrderNumber()
    {
        return $this->animalOrderNumber;
    }

    /**
     * @param string $animalOrderNumber
     */
    public function setAnimalOrderNumber($animalOrderNumber)
    {
        $this->animalOrderNumber = $animalOrderNumber;
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
     * @return string
     */
    public function getNickName()
    {
        return $this->nickName;
    }

    /**
     * @param string $nickName
     */
    public function setNickName($nickName)
    {
        $this->nickName = $nickName;
    }

    /**
     * @return int
     */
    public function getFatherVsmId()
    {
        return $this->fatherVsmId;
    }

    /**
     * @param int $fatherVsmId
     */
    public function setFatherVsmId($fatherVsmId)
    {
        $this->fatherVsmId = $fatherVsmId;
    }

    /**
     * @return int
     */
    public function getFatherId()
    {
        return $this->fatherId;
    }

    /**
     * @param int $fatherId
     */
    public function setFatherId($fatherId)
    {
        $this->fatherId = $fatherId;
    }

    /**
     * @return int
     */
    public function getMotherVsmId()
    {
        return $this->motherVsmId;
    }

    /**
     * @param int $motherVsmId
     */
    public function setMotherVsmId($motherVsmId)
    {
        $this->motherVsmId = $motherVsmId;
    }

    /**
     * @return int
     */
    public function getMotherId()
    {
        return $this->motherId;
    }

    /**
     * @param int $motherId
     */
    public function setMotherId($motherId)
    {
        $this->motherId = $motherId;
    }

    /**
     * @return string
     */
    public function getGenderInFile()
    {
        return $this->genderInFile;
    }

    /**
     * @param string $genderInFile
     */
    public function setGenderInFile($genderInFile)
    {
        $this->genderInFile = $genderInFile;
    }

    /**
     * @return string
     */
    public function getGenderInDatabase()
    {
        return $this->genderInDatabase;
    }

    /**
     * @param string $genderInDatabase
     */
    public function setGenderInDatabase($genderInDatabase)
    {
        $this->genderInDatabase = $genderInDatabase;
    }

    /**
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param \DateTime $dateOfBirth
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
    }

    /**
     * @return string
     */
    public function getBreedCode()
    {
        return $this->breedCode;
    }

    /**
     * @param string $breedCode
     */
    public function setBreedCode($breedCode)
    {
        $this->breedCode = $breedCode;
    }

    /**
     * @return string
     */
    public function getOldBreedCode()
    {
        return $this->oldBreedCode;
    }

    /**
     * @param string $oldBreedCode
     */
    public function setOldBreedCode($oldBreedCode)
    {
        $this->oldBreedCode = $oldBreedCode;
    }

    /**
     * @return boolean
     */
    public function isIsBreedCodeUpdated()
    {
        return $this->isBreedCodeUpdated;
    }

    /**
     * @param boolean $isBreedCodeUpdated
     */
    public function setIsBreedCodeUpdated($isBreedCodeUpdated)
    {
        $this->isBreedCodeUpdated = $isBreedCodeUpdated;
    }

    /**
     * @return string
     */
    public function getUbnOfBirth()
    {
        return $this->ubnOfBirth;
    }

    /**
     * @param string $ubnOfBirth
     */
    public function setUbnOfBirth($ubnOfBirth)
    {
        $this->ubnOfBirth = $ubnOfBirth;
    }

    /**
     * @return int
     */
    public function getLocationOfBirthId()
    {
        return $this->locationOfBirthId;
    }

    /**
     * @param int $locationOfBirthId
     */
    public function setLocationOfBirthId($locationOfBirthId)
    {
        $this->locationOfBirthId = $locationOfBirthId;
    }

    /**
     * @return int
     */
    public function getPedigreeRegisterId()
    {
        return $this->pedigreeRegisterId;
    }

    /**
     * @param int $pedigreeRegisterId
     */
    public function setPedigreeRegisterId($pedigreeRegisterId)
    {
        $this->pedigreeRegisterId = $pedigreeRegisterId;
    }

    /**
     * @return string
     */
    public function getBreedType()
    {
        return $this->BreedType;
    }

    /**
     * @param string $BreedType
     */
    public function setBreedType($BreedType)
    {
        $this->BreedType = $BreedType;
    }

    /**
     * @return string
     */
    public function getScrapieGenotype()
    {
        return $this->scrapieGenotype;
    }

    /**
     * @param string $scrapieGenotype
     */
    public function setScrapieGenotype($scrapieGenotype)
    {
        $this->scrapieGenotype = $scrapieGenotype;
    }


}