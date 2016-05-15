<?php

namespace AppBundle\JsonFormat;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Ram;
use AppBundle\JsonFormat\DeclareBirthJsonFormatEwe;
use AppBundle\Enumerator\AnimalType;

/**
 * Class ChildJsonFormat
 * @package AppBundle\JsonFormat
 */
class DeclareBirthJsonFormatChild
{
    /**
     * @var boolean
     */
    private $isAlive;

    /**
     * @var string
     */
    private $pedigreeCountryCode;

    /**
     * @var string
     */
    private $pedigreeNumber;

    /**
     * @var string
     */
    private $ulnCountryCode;

    /**
     * @var string
     */
    private $ulnNumber;

    /**
     * @var string
     */
    private $gender;

    /**
     * @var string
     */
    private $birthType;

    /**
     * @var float
     */
    private $birthWeight;

    /**
     * @var float
     */
    private $birthTailLength;

    /**
     * @var boolean
     */
    private $hasLambar;

    /**
     * @var DeclareBirthJsonFormatEwe
     */
    private $surrogate;

    public function __construct()
    {

    }

    /**
     * @param Animal $child
     * @param bool $usePedigree
     */
    public function setChildValues(Animal $child, $usePedigree = false)
    {
        $ulnExists = !is_null($child->getUlnCountryCode())
                  && !is_null($child->getUlnNumber());
        $genderExists = !is_null($child->getGender());

        if($ulnExists && !$usePedigree) {
            $this->setUlnCountryCode($child->getUlnCountryCode());
            $this->setUlnNumber($child->getUlnNumber());
        } else {
            $this->setPedigreeCountryCode($child->getPedigreeCountryCode());
            $this->setPedigreeNumber($child->getPedigreeNumber());
        }

        if($genderExists) {
            $this->setGender($child->getGender());
        } else {
            if($child instanceof Ram) {
                $this->setGender(AnimalType::MALE);
            } else if($child instanceof Ewe) {
                $this->setGender(AnimalType::FEMALE);
            }
        }
    }

    /**
     * @param Ewe $ewe
     * @param bool $usePedigree
     */
    public function setSurrogateValues(Ewe $ewe, $usePedigree = false)
    {
        $this->surrogate = new DeclareBirthJsonFormatEwe();

        $ulnExists = !is_null($ewe->getUlnCountryCode())
                  && !is_null($ewe->getUlnNumber());

        if($ulnExists && !$usePedigree) {
            $this->surrogate->setEweUln($ewe);
        } else {
            $this->surrogate->setEwePedigree($ewe);
        }
    }

    /**
     * @return boolean
     */
    public function getIsAlive()
    {
        return $this->isAlive;
    }

    /**
     * @param boolean $isAlive
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;
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
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getBirthType()
    {
        return $this->birthType;
    }

    /**
     * @param string $birthType
     */
    public function setBirthType($birthType)
    {
        $this->birthType = $birthType;
    }

    /**
     * @return float
     */
    public function getBirthWeight()
    {
        return $this->birthWeight;
    }

    /**
     * @param float $birthWeight
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
    }

    /**
     * @return float
     */
    public function getBirthTailLength()
    {
        return $this->birthTailLength;
    }

    /**
     * @param float $birthTailLength
     */
    public function setBirthTailLength($birthTailLength)
    {
        $this->birthTailLength = $birthTailLength;
    }

    /**
     * @return boolean
     */
    public function getHasLambar()
    {
        return $this->hasLambar;
    }

    /**
     * @param boolean $hasLambar
     */
    public function setHasLambar($hasLambar)
    {
        $this->hasLambar = $hasLambar;
    }

    /**
     * @return DeclareBirthJsonFormatEwe
     */
    public function getSurrogate()
    {
        return $this->surrogate;
    }

    /**
     * @param DeclareBirthJsonFormatEwe $surrogate
     */
    public function setSurrogate($surrogate)
    {
        $this->surrogate = $surrogate;
    }
}