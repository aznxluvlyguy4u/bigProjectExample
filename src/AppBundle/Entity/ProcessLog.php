<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\TimeUtil;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProcessLog
 * @ORM\Table(name="process_log",indexes={
 *     @ORM\Index(name="process_log_idx",
 *          columns={"type_id","category_id","sub_category_id","category","sub_category"}
 *     )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ProcessLogRepository")
 * @package AppBundle\Entity
 */
class ProcessLog
{
    use EntityClassInfo;

    const DATE_FORMAT = 'Y-m-d H:i:s';
    const DATE_NULL_RESULT = '-';

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
     * @ORM\Column(type="integer", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("integer")
     */
    private $typeId;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $type;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $category;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $categoryId;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $subCategory;

    /**
     * @var integer|null
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $subCategoryId;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=false)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $startDate;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", options={"default":null}, nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $endDate;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false, options={"default":true})
     * @Assert\NotNull
     * @JMS\Type("boolean")
     */
    private $isActive;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $description;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $errorMessage;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $debuggingData;

    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->isActive = true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTypeId(): int
    {
        return $this->typeId;
    }

    /**
     * @param int $typeId
     * @return ProcessLog
     */
    public function setTypeId(int $typeId): ProcessLog
    {
        $this->typeId = $typeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ProcessLog
     */
    public function setType(string $type): ProcessLog
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * @param string|null $category
     * @return ProcessLog
     */
    public function setCategory(?string $category): ProcessLog
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * @param int|null $categoryId
     * @return ProcessLog
     */
    public function setCategoryId(?int $categoryId): ProcessLog
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    /**
     * @param string|null $subCategory
     * @return ProcessLog
     */
    public function setSubCategory(?string $subCategory): ProcessLog
    {
        $this->subCategory = $subCategory;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSubCategoryId(): ?int
    {
        return $this->subCategoryId;
    }

    /**
     * @param int|null $subCategoryId
     * @return ProcessLog
     */
    public function setSubCategoryId(?int $subCategoryId): ProcessLog
    {
        $this->subCategoryId = $subCategoryId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     * @return ProcessLog
     */
    public function setIsActive(bool $isActive): ProcessLog
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     * @return ProcessLog
     */
    public function setStartDate(DateTime $startDate): ProcessLog
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime|null $endDate
     * @return ProcessLog
     */
    public function setEndDate(?DateTime $endDate): ProcessLog
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return ProcessLog
     */
    public function setDescription(?string $description): ProcessLog
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string|null $description
     * @return ProcessLog
     */
    public function addToDescription(?string $description): ProcessLog
    {
        if ($this->description) {
            $this->description = $this->description . '. ' . $description;
        } else {
            $this->description = $description;
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @param string|null $errorMessage
     * @return ProcessLog
     */
    public function setErrorMessage(?string $errorMessage): ProcessLog
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * @param string|null $errorMessage
     * @return ProcessLog
     */
    public function addToErrorMessage(?string $errorMessage): ProcessLog
    {
        if ($this->errorMessage) {
            $this->errorMessage = $this->errorMessage . '. ' . $errorMessage;
        } else {
            $this->errorMessage = $errorMessage;
        }
        return $this;
    }


    /**
     * @return string|null
     */
    public function getDebuggingData(): ?string
    {
        return $this->debuggingData;
    }

    /**
     * @param string|null $debuggingData
     * @return ProcessLog
     */
    public function setDebuggingData(?string $debuggingData): ProcessLog
    {
        $this->debuggingData = $debuggingData;
        return $this;
    }

    /**
     * @param string|null $debuggingData
     * @return ProcessLog
     */
    public function addToDebuggingData(?string $debuggingData): ProcessLog
    {
        if ($this->debuggingData) {
            $this->debuggingData = $this->debuggingData . '. ' . $debuggingData;
        } else {
            $this->debuggingData = $debuggingData;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getStartDateAsString(): string
    {
        return $this->startDate ? $this->startDate->format(self::DATE_FORMAT): self::DATE_NULL_RESULT;
    }

    /**
     * @return string
     */
    public function getEndDateAsString(): string
    {
        return $this->endDate ? $this->endDate->format(self::DATE_FORMAT): self::DATE_NULL_RESULT;
    }

    /**
     * @return string
     */
    public function duration(): string {
        if ($this->endDate == null || $this->startDate == null) {
            return self::DATE_NULL_RESULT;
        }
        $diff = $this->endDate->diff($this->startDate);
        return empty($diff->d) ?
            $diff->format("%H:%I:%S") :
            $diff->format("%d days %H:%I:%S")
            ;
    }
}
