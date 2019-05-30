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
 *          columns={"type_id","category_id","sub_category_id"}
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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $category;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     * @JMS\Type("integer")
     */
    private $categoryId;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $subCategory;

    /**
     * @var integer
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
     * @var DateTime
     * @ORM\Column(type="datetime", options={"default":null}, nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $endDate;


    public function __construct()
    {
        $this->startDate = new \DateTime();
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
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     * @return ProcessLog
     */
    public function setCategory(string $category): ProcessLog
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return ProcessLog
     */
    public function setCategoryId(int $categoryId): ProcessLog
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubCategory(): string
    {
        return $this->subCategory;
    }

    /**
     * @param string $subCategory
     * @return ProcessLog
     */
    public function setSubCategory(string $subCategory): ProcessLog
    {
        $this->subCategory = $subCategory;
        return $this;
    }

    /**
     * @return int
     */
    public function getSubCategoryId(): int
    {
        return $this->subCategoryId;
    }

    /**
     * @param int $subCategoryId
     * @return ProcessLog
     */
    public function setSubCategoryId(int $subCategoryId): ProcessLog
    {
        $this->subCategoryId = $subCategoryId;
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
     * @return DateTime
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    /**
     * @param DateTime $endDate
     * @return ProcessLog
     */
    public function setEndDate(DateTime $endDate): ProcessLog
    {
        $this->endDate = $endDate;
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