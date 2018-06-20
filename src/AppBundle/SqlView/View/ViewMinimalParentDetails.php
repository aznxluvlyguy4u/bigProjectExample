<?php


namespace AppBundle\SqlView\View;

use JMS\Serializer\Annotation as JMS;

class ViewMinimalParentDetails implements SqlViewInterface
{
    /**
     * @var integer
     * @JMS\Type("integer")
     */
    private $animalId;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $uln;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $stn;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $ddMmYyyyDateOfBirth;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $generalAppearance;

    /**
     * @var float
     * @JMS\Type("float")
     */
    private $muscularity;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    private $nLing;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $production;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $formattedPredicate;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breedCode;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $scrapieGenotype;

    /**
     * @var string
     * @JMS\Type("string")
     */
    private $breedTypeAsDutchFirstLetter;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    private $isPublic;


    /**
     * @return string
     */
    static function getPrimaryKeyName()
    {
        return 'animal_id';
    }

    /**
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getAnimalId();
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
     * @return ViewMinimalParentDetails
     */
    public function setAnimalId($animalId)
    {
        $this->animalId = $animalId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUln()
    {
        return $this->uln;
    }

    /**
     * @param string $uln
     * @return ViewMinimalParentDetails
     */
    public function setUln($uln)
    {
        $this->uln = $uln;
        return $this;
    }

    /**
     * @return string
     */
    public function getStn()
    {
        return $this->stn;
    }

    /**
     * @param string $stn
     * @return ViewMinimalParentDetails
     */
    public function setStn($stn)
    {
        $this->stn = $stn;
        return $this;
    }

    /**
     * @return string
     */
    public function getDdMmYyyyDateOfBirth()
    {
        return $this->ddMmYyyyDateOfBirth;
    }

    /**
     * @param string $ddMmYyyyDateOfBirth
     * @return ViewMinimalParentDetails
     */
    public function setDdMmYyyyDateOfBirth($ddMmYyyyDateOfBirth)
    {
        $this->ddMmYyyyDateOfBirth = $ddMmYyyyDateOfBirth;
        return $this;
    }

    /**
     * @return float
     */
    public function getGeneralAppearance()
    {
        return $this->generalAppearance;
    }

    /**
     * @param float $generalAppearance
     * @return ViewMinimalParentDetails
     */
    public function setGeneralAppearance($generalAppearance)
    {
        $this->generalAppearance = $generalAppearance;
        return $this;
    }

    /**
     * @return float
     */
    public function getMuscularity()
    {
        return $this->muscularity;
    }

    /**
     * @param float $muscularity
     * @return ViewMinimalParentDetails
     */
    public function setMuscularity($muscularity)
    {
        $this->muscularity = $muscularity;
        return $this;
    }

    /**
     * @return int
     */
    public function getNLing()
    {
        return $this->nLing;
    }

    /**
     * @param int $nLing
     * @return ViewMinimalParentDetails
     */
    public function setNLing($nLing)
    {
        $this->nLing = $nLing;
        return $this;
    }

    /**
     * @return string
     */
    public function getProduction()
    {
        return $this->production;
    }

    /**
     * @param string $production
     * @return ViewMinimalParentDetails
     */
    public function setProduction($production)
    {
        $this->production = $production;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedPredicate()
    {
        return $this->formattedPredicate;
    }

    /**
     * @param string $formattedPredicate
     * @return ViewMinimalParentDetails
     */
    public function setFormattedPredicate($formattedPredicate)
    {
        $this->formattedPredicate = $formattedPredicate;
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
     * @return ViewMinimalParentDetails
     */
    public function setBreedCode($breedCode)
    {
        $this->breedCode = $breedCode;
        return $this;
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
     * @return ViewMinimalParentDetails
     */
    public function setScrapieGenotype($scrapieGenotype)
    {
        $this->scrapieGenotype = $scrapieGenotype;
        return $this;
    }

    /**
     * @return string
     */
    public function getBreedTypeAsDutchFirstLetter()
    {
        return $this->breedTypeAsDutchFirstLetter;
    }

    /**
     * @param string $breedTypeAsDutchFirstLetter
     * @return ViewMinimalParentDetails
     */
    public function setBreedTypeAsDutchFirstLetter($breedTypeAsDutchFirstLetter)
    {
        $this->breedTypeAsDutchFirstLetter = $breedTypeAsDutchFirstLetter;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     * @return ViewMinimalParentDetails
     */
    public function setIsPublic(bool $isPublic): ViewMinimalParentDetails
    {
        $this->isPublic = $isPublic;
        return $this;
    }
}