<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewLocationDetails implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $locationId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ubn;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ownerFullName;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $city;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $state;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $pedigreeRegisterAbbreviations;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $tePedigreeRegisterAbbreviations;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breederNumbers;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $teBreederNumbers;

    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'location_id';
    }

    /**
     * @return int
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param int $locationId
     * @return ViewLocationDetails
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * @param string $ubn
     * @return ViewLocationDetails
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;
        return $this;
    }

    /**
     * @return string
     */
    public function getOwnerFullName()
    {
        return $this->ownerFullName;
    }

    /**
     * @param string $ownerFullName
     * @return ViewLocationDetails
     */
    public function setOwnerFullName($ownerFullName)
    {
        $this->ownerFullName = $ownerFullName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return ViewLocationDetails
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return ViewLocationDetails
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     * @return array
     */
    public function getPedigreeRegisterAbbreviations()
    {
        return explode(',',$this->pedigreeRegisterAbbreviations);
    }

    /**
     * @param array $pedigreeRegisterAbbreviations
     * @return ViewLocationDetails
     */
    public function setPedigreeRegisterAbbreviations($pedigreeRegisterAbbreviations = [])
    {
        $this->pedigreeRegisterAbbreviations = implode(',',$pedigreeRegisterAbbreviations);
        return $this;
    }

    /**
     * @return array
     */
    public function getTePedigreeRegisterAbbreviations()
    {
        return explode(',',$this->tePedigreeRegisterAbbreviations);
    }

    /**
     * @param array $tePedigreeRegisterAbbreviations
     * @return ViewLocationDetails
     */
    public function setTePedigreeRegisterAbbreviations($tePedigreeRegisterAbbreviations = [])
    {
        $this->tePedigreeRegisterAbbreviations = implode(',',$tePedigreeRegisterAbbreviations);
        return $this;
    }

    /**
     * @return array
     */
    public function getBreederNumbers()
    {
        return explode(',',$this->breederNumbers);
    }

    /**
     * @param array $breederNumbers
     * @return ViewLocationDetails
     */
    public function setBreederNumbers($breederNumbers = [])
    {
        $this->breederNumbers = implode(',',$breederNumbers);
        return $this;
    }

    /**
     * @return array
     */
    public function getTeBreederNumbers()
    {
        return explode(',',$this->teBreederNumbers);
    }

    /**
     * @param array $teBreederNumbers
     * @return ViewLocationDetails
     */
    public function setTeBreederNumbers($teBreederNumbers = [])
    {
        $this->teBreederNumbers = implode(',',$teBreederNumbers);
        return $this;
    }


}