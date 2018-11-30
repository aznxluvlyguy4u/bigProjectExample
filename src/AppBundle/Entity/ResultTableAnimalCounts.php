<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class ResultTableAnimalCounts
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ResultTableAnimalCountsRepository")
 * @package AppBundle\Entity
 */
class ResultTableAnimalCounts
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $batchStartDate;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $latestRvoLeadingSyncDateBeforeBatchStart;

    /**
     * @var Location
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Location", inversedBy="resultTableAnimalCounts")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Exclude
     */
    private $location;

    /**
     * @var Company
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\Company", inversedBy="resultTableAnimalCounts")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Company")
     * @JMS\Exclude
     */
    private $company;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $animalsOneYearOrOlder;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $animalsYoungerThanOneYear;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $allAnimals;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ewesOneYearOrOlder;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ewesYoungerThanOneYear;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ramsOneYearOrOlder;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $ramsYoungerThanOneYear;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $neutersOneYearOrOlder;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $neutersYoungerThanOneYear;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeAnimalsOneYearOrOlder;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeAnimalsYoungerThanOneYear;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $nonPedigreeAnimalsOneYearOrOlder;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $nonPedigreeAnimalsYoungerThanOneYear;

    /**
     * @var integer|null
     *
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $pedigreeEwesSixMonthsOrOlder;

    /**
     * ResultTableAnimalCounts constructor.
     */
    public function __construct()
    {
        $this->logDate = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ResultTableAnimalCounts
     */
    public function setId(int $id): ResultTableAnimalCounts
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param mixed $logDate
     * @return ResultTableAnimalCounts
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBatchStartDate()
    {
        return $this->batchStartDate;
    }

    /**
     * @param mixed $batchStartDate
     * @return ResultTableAnimalCounts
     */
    public function setBatchStartDate($batchStartDate)
    {
        $this->batchStartDate = $batchStartDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLatestRvoLeadingSyncDateBeforeBatchStart()
    {
        return $this->latestRvoLeadingSyncDateBeforeBatchStart;
    }

    /**
     * @param mixed $latestRvoLeadingSyncDateBeforeBatchStart
     * @return ResultTableAnimalCounts
     */
    public function setLatestRvoLeadingSyncDateBeforeBatchStart($latestRvoLeadingSyncDateBeforeBatchStart)
    {
        $this->latestRvoLeadingSyncDateBeforeBatchStart = $latestRvoLeadingSyncDateBeforeBatchStart;
        return $this;
    }

    /**
     * @return Location
     */
    public function getLocation(): Location
    {
        return $this->location;
    }

    /**
     * @param Location $location
     * @return ResultTableAnimalCounts
     */
    public function setLocation(Location $location): ResultTableAnimalCounts
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     * @return ResultTableAnimalCounts
     */
    public function setCompany(Company $company): ResultTableAnimalCounts
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAnimalsOneYearOrOlder(): ?int
    {
        return $this->animalsOneYearOrOlder;
    }

    /**
     * @param int|null $animalsOneYearOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setAnimalsOneYearOrOlder(?int $animalsOneYearOrOlder): ResultTableAnimalCounts
    {
        $this->animalsOneYearOrOlder = $animalsOneYearOrOlder;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAnimalsYoungerThanOneYear(): ?int
    {
        return $this->animalsYoungerThanOneYear;
    }

    /**
     * @param int|null $animalsYoungerThanOneYear
     * @return ResultTableAnimalCounts
     */
    public function setAnimalsYoungerThanOneYear(?int $animalsYoungerThanOneYear): ResultTableAnimalCounts
    {
        $this->animalsYoungerThanOneYear = $animalsYoungerThanOneYear;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getAllAnimals(): ?int
    {
        return $this->allAnimals;
    }

    /**
     * @param int|null $allAnimals
     * @return ResultTableAnimalCounts
     */
    public function setAllAnimals(?int $allAnimals): ResultTableAnimalCounts
    {
        $this->allAnimals = $allAnimals;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEwesOneYearOrOlder(): ?int
    {
        return $this->ewesOneYearOrOlder;
    }

    /**
     * @param int|null $ewesOneYearOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setEwesOneYearOrOlder(?int $ewesOneYearOrOlder): ResultTableAnimalCounts
    {
        $this->ewesOneYearOrOlder = $ewesOneYearOrOlder;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getEwesYoungerThanOneYear(): ?int
    {
        return $this->ewesYoungerThanOneYear;
    }

    /**
     * @param int|null $ewesYoungerThanOneYear
     * @return ResultTableAnimalCounts
     */
    public function setEwesYoungerThanOneYear(?int $ewesYoungerThanOneYear): ResultTableAnimalCounts
    {
        $this->ewesYoungerThanOneYear = $ewesYoungerThanOneYear;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRamsOneYearOrOlder(): ?int
    {
        return $this->ramsOneYearOrOlder;
    }

    /**
     * @param int|null $ramsOneYearOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setRamsOneYearOrOlder(?int $ramsOneYearOrOlder): ResultTableAnimalCounts
    {
        $this->ramsOneYearOrOlder = $ramsOneYearOrOlder;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRamsYoungerThanOneYear(): ?int
    {
        return $this->ramsYoungerThanOneYear;
    }

    /**
     * @param int|null $ramsYoungerThanOneYear
     * @return ResultTableAnimalCounts
     */
    public function setRamsYoungerThanOneYear(?int $ramsYoungerThanOneYear): ResultTableAnimalCounts
    {
        $this->ramsYoungerThanOneYear = $ramsYoungerThanOneYear;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNeutersOneYearOrOlder(): ?int
    {
        return $this->neutersOneYearOrOlder;
    }

    /**
     * @param int|null $neutersOneYearOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setNeutersOneYearOrOlder(?int $neutersOneYearOrOlder): ResultTableAnimalCounts
    {
        $this->neutersOneYearOrOlder = $neutersOneYearOrOlder;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNeutersYoungerThanOneYear(): ?int
    {
        return $this->neutersYoungerThanOneYear;
    }

    /**
     * @param int|null $neutersYoungerThanOneYear
     * @return ResultTableAnimalCounts
     */
    public function setNeutersYoungerThanOneYear(?int $neutersYoungerThanOneYear): ResultTableAnimalCounts
    {
        $this->neutersYoungerThanOneYear = $neutersYoungerThanOneYear;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPedigreeAnimalsOneYearOrOlder(): ?int
    {
        return $this->pedigreeAnimalsOneYearOrOlder;
    }

    /**
     * @param int|null $pedigreeAnimalsOneYearOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setPedigreeAnimalsOneYearOrOlder(?int $pedigreeAnimalsOneYearOrOlder): ResultTableAnimalCounts
    {
        $this->pedigreeAnimalsOneYearOrOlder = $pedigreeAnimalsOneYearOrOlder;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPedigreeAnimalsYoungerThanOneYear(): ?int
    {
        return $this->pedigreeAnimalsYoungerThanOneYear;
    }

    /**
     * @param int|null $pedigreeAnimalsYoungerThanOneYear
     * @return ResultTableAnimalCounts
     */
    public function setPedigreeAnimalsYoungerThanOneYear(?int $pedigreeAnimalsYoungerThanOneYear): ResultTableAnimalCounts
    {
        $this->pedigreeAnimalsYoungerThanOneYear = $pedigreeAnimalsYoungerThanOneYear;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNonPedigreeAnimalsOneYearOrOlder(): ?int
    {
        return $this->nonPedigreeAnimalsOneYearOrOlder;
    }

    /**
     * @param int|null $nonPedigreeAnimalsOneYearOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setNonPedigreeAnimalsOneYearOrOlder(?int $nonPedigreeAnimalsOneYearOrOlder): ResultTableAnimalCounts
    {
        $this->nonPedigreeAnimalsOneYearOrOlder = $nonPedigreeAnimalsOneYearOrOlder;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNonPedigreeAnimalsYoungerThanOneYear(): ?int
    {
        return $this->nonPedigreeAnimalsYoungerThanOneYear;
    }

    /**
     * @param int|null $nonPedigreeAnimalsYoungerThanOneYear
     * @return ResultTableAnimalCounts
     */
    public function setNonPedigreeAnimalsYoungerThanOneYear(?int $nonPedigreeAnimalsYoungerThanOneYear): ResultTableAnimalCounts
    {
        $this->nonPedigreeAnimalsYoungerThanOneYear = $nonPedigreeAnimalsYoungerThanOneYear;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPedigreeEwesSixMonthsOrOlder(): ?int
    {
        return $this->pedigreeEwesSixMonthsOrOlder;
    }

    /**
     * @param int|null $pedigreeEwesSixMonthsOrOlder
     * @return ResultTableAnimalCounts
     */
    public function setPedigreeEwesSixMonthsOrOlder(?int $pedigreeEwesSixMonthsOrOlder): ResultTableAnimalCounts
    {
        $this->pedigreeEwesSixMonthsOrOlder = $pedigreeEwesSixMonthsOrOlder;
        return $this;
    }


}