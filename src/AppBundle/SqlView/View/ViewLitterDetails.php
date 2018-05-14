<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewLitterDetails implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $litterId;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $pedigreeRegisterRegistrationId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breederNumber;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breedCode;

    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $locationOfBirthId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ubnOfBirth;

    /**
     * @var boolean
     * @JMS\Type("boolean")
     */
    private $isCompleted;

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'litter_id';
    }

    /**
     * @return int
     */
    public function getLitterId()
    {
        return $this->litterId;
    }

    /**
     * @param int $litterId
     * @return ViewLitterDetails
     */
    public function setLitterId($litterId)
    {
        $this->litterId = $litterId;
        return $this;
    }

    /**
     * @return int
     */
    public function getPedigreeRegisterRegistrationId()
    {
        return $this->pedigreeRegisterRegistrationId;
    }

    /**
     * @param int $pedigreeRegisterRegistrationId
     * @return ViewLitterDetails
     */
    public function setPedigreeRegisterRegistrationId($pedigreeRegisterRegistrationId)
    {
        $this->pedigreeRegisterRegistrationId = $pedigreeRegisterRegistrationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getBreederNumber()
    {
        return $this->breederNumber;
    }

    /**
     * @param string $breederNumber
     * @return ViewLitterDetails
     */
    public function setBreederNumber($breederNumber)
    {
        $this->breederNumber = $breederNumber;
        return $this;
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
     * @return ViewLitterDetails
     */
    public function setBreedCode($breedCode)
    {
        $this->breedCode = $breedCode;
        return $this;
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
     * @return ViewLitterDetails
     */
    public function setLocationOfBirthId($locationOfBirthId)
    {
        $this->locationOfBirthId = $locationOfBirthId;
        return $this;
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
     * @return ViewLitterDetails
     */
    public function setUbnOfBirth($ubnOfBirth)
    {
        $this->ubnOfBirth = $ubnOfBirth;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        return $this->isCompleted;
    }

    /**
     * @param bool $isCompleted
     * @return ViewLitterDetails
     */
    public function setIsCompleted($isCompleted)
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }


}