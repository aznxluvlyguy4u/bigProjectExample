<?php


namespace AppBundle\Entity;

use AppBundle\Enumerator\ProcessStatusSlot;
use AppBundle\Enumerator\ProcessStatusType;
use AppBundle\model\process\ProcessDetails;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\TimeUtil;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProcessLog
 * @ORM\Table(name="process_status")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ProcessStatusRepository")
 * @package AppBundle\Entity
 */
class ProcessStatus
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Type("integer")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, unique=true)
     * @JMS\Type("integer")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @JMS\Type("string")
     */
    private $name;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $errorCode;

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
    private $debugErrorMessage;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     * @JMS\Type("DateTime")
     */
    private $startedAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Type("DateTime")
     */
    private $bumpedAt;

    /**
     * @var DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\Type("DateTime")
     */
    private $finishedAt;

    /**
     * Progress in percentage
     *
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @JMS\Type("integer")
     */
    private $progress;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @JMS\Type("integer")
     */
    private $newCount;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @JMS\Type("integer")
     */
    private $updatedCount;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @JMS\Type("integer")
     */
    private $skippedCount;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @JMS\Type("integer")
     */
    private $processed;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false, options={"default":0,"unsigned":true})
     * @JMS\Type("integer")
     */
    private $total;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $log;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isCancelled;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $recalculate;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isLocked;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("duration")
     * @JMS\Type("string")
     * @return string
     */
    public function duration(): string {
        return TimeUtil::durationText($this->startedAt, $this->finishedAt);
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("eta")
     * @JMS\Type("string")
     * @return string
     */
    public function estimatedTimeOfArrival(): string {
        return $this->getFinishedAt() ? '-' :
            TimeUtil::estimatedTimeOfArrival(
                $this->processed,
                $this->total,
                $this->startedAt,
                $this->bumpedAt
            );
    }

    public function __construct(int $type = ProcessStatusType::ANIMAL_RESIDENCE_DAILY_MATCH_WITH_CURRENT_LIVESTOCK)
    {
        $this->type = $type;
        $this->name = ProcessStatusType::getName($type);

        $now = new \DateTime();
        $this->finishedAt = $now;

        $this->initializeDefaultValues($now, false);
    }


    private function initializeDefaultValues(\DateTime $now, bool $recalculate = false)
    {
        $this->startedAt = $now;
        $this->recalculate = $recalculate;

        $this->isCancelled = false;
        $this->isLocked = false;

        $this->progress = 0;
        $this->processed = 0;
        $this->total = 0;
        $this->newCount = 0;
        $this->skippedCount = 0;
        $this->updatedCount = 0;

        $this->errorCode = null;
        $this->errorMessage = null;
        $this->debugErrorMessage = null;
    }


    public function reset(\DateTime $startedAt, bool $recalculate)
    {
        $this->initializeDefaultValues($startedAt, $recalculate);
        $this->setFinishedAt(null);
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int  $id
     * @return ProcessStatus
     */
    public function setId(int $id): ProcessStatus
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param  int  $type
     * @return ProcessStatus
     */
    public function setType(int $type): ProcessStatus
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProcessStatus
     */
    public function setName(string $name): ProcessStatus
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @param  string|null  $errorCode
     * @return ProcessStatus
     */
    public function setErrorCode(?string $errorCode): ProcessStatus
    {
        $this->errorCode = $errorCode;
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
     * @param  string|null  $errorMessage
     * @return ProcessStatus
     */
    public function setErrorMessage(?string $errorMessage): ProcessStatus
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDebugErrorMessage(): ?string
    {
        return $this->debugErrorMessage;
    }

    /**
     * @param  string|null  $debugErrorMessage
     * @return ProcessStatus
     */
    public function setDebugErrorMessage(?string $debugErrorMessage): ProcessStatus
    {
        $this->debugErrorMessage = $debugErrorMessage;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    /**
     * @param  DateTime  $startedAt
     * @return ProcessStatus
     */
    public function setStartedAt(DateTime $startedAt): ProcessStatus
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getBumpedAt(): ?DateTime
    {
        return $this->bumpedAt;
    }

    /**
     * @param  DateTime|null  $bumpedAt
     * @return ProcessStatus
     */
    public function setBumpedAt(?DateTime $bumpedAt): ProcessStatus
    {
        $this->bumpedAt = $bumpedAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getFinishedAt(): ?DateTime
    {
        return $this->finishedAt;
    }

    /**
     * @return boolean
     */
    public function isFinished(): bool
    {
        return $this->finishedAt != null;
    }

    /**
     * @param  DateTime|null  $finishedAt
     * @return ProcessStatus
     */
    public function setFinishedAt(?DateTime $finishedAt): ProcessStatus
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }

    /**
     * @param  int  $progress
     * @return ProcessStatus
     */
    public function setProgress(int $progress): ProcessStatus
    {
        $this->progress = $progress;
        return $this;
    }

    /**
     * @return int
     */
    public function getNewCount(): int
    {
        return $this->newCount;
    }

    /**
     * @param  int  $newCount
     * @return ProcessStatus
     */
    public function setNewCount(int $newCount): ProcessStatus
    {
        $this->newCount = $newCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    /**
     * @param  int  $updatedCount
     * @return ProcessStatus
     */
    public function setUpdatedCount(int $updatedCount): ProcessStatus
    {
        $this->updatedCount = $updatedCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * @param  int  $skippedCount
     * @return ProcessStatus
     */
    public function setSkippedCount(int $skippedCount): ProcessStatus
    {
        $this->skippedCount = $skippedCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getProcessed(): int
    {
        return $this->processed;
    }

    /**
     * @param  int  $processed
     * @return ProcessStatus
     */
    public function setProcessed(int $processed): ProcessStatus
    {
        $this->processed = $processed;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param  int  $total
     * @return ProcessStatus
     */
    public function setTotal(int $total): ProcessStatus
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLog(): ?string
    {
        return $this->log;
    }

    /**
     * @param  string|null  $log
     * @return ProcessStatus
     */
    public function setLog(?string $log): ProcessStatus
    {
        $this->log = $log;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    /**
     * @return bool
     */
    public function isRecalculate(): bool
    {
        return $this->recalculate;
    }

    /**
     * @param  bool  $recalculate
     * @return ProcessStatus
     */
    public function setRecalculate(bool $recalculate): ProcessStatus
    {
        $this->recalculate = $recalculate;
        return $this;
    }

    public function setProcessDetails(
        ProcessDetails $processDetails,
        bool $setFinishedAt = false
    ): ProcessStatus
    {
        $now = new \DateTime();

        $this->setBumpedAt($now);
        if ($setFinishedAt) {
            $this->setFinishedAt($now);
        }

        $this->setTotal($processDetails->getTotal());
        $this->setProcessed($processDetails->getProcessed());
        $this->setNewCount($processDetails->getNew());
        $this->setUpdatedCount($processDetails->getUpdated());
        $this->setSkippedCount($processDetails->getSkipped());

        if (empty($processDetails->getTotal())) {
            $this->setProgress(100);
        } else {
            $this->setProgress(
                intval(($processDetails->getProcessed() / $processDetails->getTotal()) * 100)
            );
        }

        $this->setLog($processDetails->getLogMessage());

        return $this;
    }

    /**
     * @param  bool  $isCancelled
     * @return ProcessStatus
     */
    public function setIsCancelled(bool $isCancelled): ProcessStatus
    {
        $this->isCancelled = $isCancelled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    /**
     * @param  bool  $isLocked
     * @return ProcessStatus
     */
    public function setIsLocked(bool $isLocked): ProcessStatus
    {
        $this->isLocked = $isLocked;
        return $this;
    }

}
