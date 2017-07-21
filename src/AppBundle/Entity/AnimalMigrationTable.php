<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;


/**
 * Class AnimalMigrationTable
 * @ORM\Table(name="animal_migration_table", indexes={@ORM\Index(name="migration_idx", columns={"vsm_id", "animal_id", "mother_id", "father_id", "gender_in_database"})})
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalMigrationTableRepository")
 * @package AppBundle\Entity
 */
class AnimalMigrationTable
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
    private $stnPrefixLetters;

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
     * This the actual gender to be saved to the database.
     * The final corrected value will be saved here and the old value is saved in the 'correctedGender' column
     * 
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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $correctedGender;

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
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isUbnUpdated;


    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isUlnUpdated;


    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isStnUpdated;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isAnimalOrderNumberUpdated;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isFatherUpdated;

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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $deletedStnOrigin;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $deletedUlnOrigin;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isCorrectRecord;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isRecordMigrated;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $deletedFatherVsmId;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $deletedMotherVsmId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isNewImportAnimal;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isUnreliableParent;

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
    public function getStnPrefixLetters()
    {
        return $this->stnPrefixLetters;
    }

    /**
     * @param string $stnPrefixLetters
     * @return AnimalMigrationTable
     */
    public function setStnPrefixLetters($stnPrefixLetters)
    {
        $this->stnPrefixLetters = $stnPrefixLetters;
        return $this;
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
     * @return string
     */
    public function getCorrectedGender()
    {
        return $this->correctedGender;
    }

    /**
     * @param string $correctedGender
     */
    public function setCorrectedGender($correctedGender)
    {
        $this->correctedGender = $correctedGender;
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

    /**
     * @return boolean
     */
    public function isUbnUpdated()
    {
        return $this->isUbnUpdated;
    }

    /**
     * @param boolean $isUbnUpdated
     */
    public function setIsUbnUpdated($isUbnUpdated)
    {
        $this->isUbnUpdated = $isUbnUpdated;
    }

    /**
     * @return boolean
     */
    public function isUlnUpdated()
    {
        return $this->isUlnUpdated;
    }

    /**
     * @param boolean $isUlnUpdated
     */
    public function setIsUlnUpdated($isUlnUpdated)
    {
        $this->isUlnUpdated = $isUlnUpdated;
    }

    /**
     * @return boolean
     */
    public function isStnUpdated()
    {
        return $this->isStnUpdated;
    }

    /**
     * @param boolean $isStnUpdated
     */
    public function setIsStnUpdated($isStnUpdated)
    {
        $this->isStnUpdated = $isStnUpdated;
    }

    /**
     * @return boolean
     */
    public function isAnimalOrderNumberUpdated()
    {
        return $this->isAnimalOrderNumberUpdated;
    }

    /**
     * @param boolean $isAnimalOrderNumberUpdated
     */
    public function setIsAnimalOrderNumberUpdated($isAnimalOrderNumberUpdated)
    {
        $this->isAnimalOrderNumberUpdated = $isAnimalOrderNumberUpdated;
    }

    /**
     * @return boolean
     */
    public function isIsFatherUpdated()
    {
        return $this->isFatherUpdated;
    }

    /**
     * @param boolean $isFatherUpdated
     */
    public function setIsFatherUpdated($isFatherUpdated)
    {
        $this->isFatherUpdated = $isFatherUpdated;
    }

    /**
     * @return string
     */
    public function getDeletedStnOrigin()
    {
        return $this->deletedStnOrigin;
    }

    /**
     * @param string $deletedStnOrigin
     */
    public function setDeletedStnOrigin($deletedStnOrigin)
    {
        $this->deletedStnOrigin = $deletedStnOrigin;
    }

    /**
     * @return string
     */
    public function getDeletedUlnOrigin()
    {
        return $this->deletedUlnOrigin;
    }

    /**
     * @param string $deletedUlnOrigin
     */
    public function setDeletedUlnOrigin($deletedUlnOrigin)
    {
        $this->deletedUlnOrigin = $deletedUlnOrigin;
    }

    /**
     * @return boolean
     */
    public function isIsCorrectRecord()
    {
        return $this->isCorrectRecord;
    }

    /**
     * @param boolean $isCorrectRecord
     */
    public function setIsCorrectRecord($isCorrectRecord)
    {
        $this->isCorrectRecord = $isCorrectRecord;
    }

    /**
     * @return boolean
     */
    public function isIsRecordMigrated()
    {
        return $this->isRecordMigrated;
    }

    /**
     * @param boolean $isRecordMigrated
     */
    public function setIsRecordMigrated($isRecordMigrated)
    {
        $this->isRecordMigrated = $isRecordMigrated;
    }

    /**
     * @return int
     */
    public function getDeletedFatherVsmId()
    {
        return $this->deletedFatherVsmId;
    }

    /**
     * @param int $deletedFatherVsmId
     */
    public function setDeletedFatherVsmId($deletedFatherVsmId)
    {
        $this->deletedFatherVsmId = $deletedFatherVsmId;
    }

    /**
     * @return int
     */
    public function getDeletedMotherVsmId()
    {
        return $this->deletedMotherVsmId;
    }

    /**
     * @param int $deletedMotherVsmId
     */
    public function setDeletedMotherVsmId($deletedMotherVsmId)
    {
        $this->deletedMotherVsmId = $deletedMotherVsmId;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }

    /**
     * @return bool
     */
    public function isNewImportAnimal()
    {
        return $this->isNewImportAnimal;
    }

    /**
     * @param bool $isNewImportAnimal
     * @return AnimalMigrationTable
     */
    public function setIsNewImportAnimal($isNewImportAnimal)
    {
        $this->isNewImportAnimal = $isNewImportAnimal;
        return $this;
    }


    /**
     * @return bool
     */
    public function isUnreliableParent()
    {
        return $this->isUnreliableParent;
    }

    /**
     * @param bool $isUnreliableParent
     * @return AnimalMigrationTable
     */
    public function setIsUnreliableParent($isUnreliableParent)
    {
        $this->isUnreliableParent = $isUnreliableParent;
        return $this;
    }

    
}