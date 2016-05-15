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
     * @var string
     */
    private $alive;

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
     * @var integer
     */
    private $birthWeight;

    /**
     * @var integer
     */
    private $birthTailLength;

    /**
     * @var string
     */
    private $isLambar;

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
     * @return string
     */
    public function getIsAlive()
    {
        return $this->alive;
    }

    /**
     * @param string $isAlive
     */
    public function setIsAlive($isAlive)
    {
        $this->alive = $isAlive;
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
     * @return int
     */
    public function getBirthWeight()
    {
        return $this->birthWeight;
    }

    /**
     * @param int $birthWeight
     */
    public function setBirthWeight($birthWeight)
    {
        $this->birthWeight = $birthWeight;
    }

    /**
     * @return int
     */
    public function getBirthTailLength()
    {
        return $this->birthTailLength;
    }

    /**
     * @param int $birthTailLength
     */
    public function setBirthTailLength($birthTailLength)
    {
        $this->birthTailLength = $birthTailLength;
    }

    /**
     * @return string
     */
    public function getIsLambar()
    {
        return $this->isLambar;
    }

    /**
     * @param string $isLambar
     */
    public function setIsLambar($isLambar)
    {
        $this->isLambar = $isLambar;
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