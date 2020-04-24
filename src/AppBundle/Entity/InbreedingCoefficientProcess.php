<?php


namespace AppBundle\Entity;

use AppBundle\Enumerator\InbreedingCoefficientProcessSlot;
use AppBundle\model\process\ProcessDetails;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\TimeUtil;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class InbreedingCoefficientProcess
 * @ORM\Table(name="inbreeding_coefficient_process")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InbreedingCoefficientProcessRepository")
 * @package AppBundle\Entity
 */
class InbreedingCoefficientProcess
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
    private $slot;

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
     * @var string
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

    public function __construct(int $slot = InbreedingCoefficientProcessSlot::ADMIN)
    {
        $this->slot = $slot;

        $now = new \DateTime();
        $this->finishedAt = $now;

        $this->initializeDefaultValues($now, false);
    }


    private function initializeDefaultValues(\DateTime $now, bool $recalculate)
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
     * @return InbreedingCoefficientProcess
     */
    public function setId(int $id): InbreedingCoefficientProcess
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getSlot(): int
    {
        return $this->slot;
    }

    public function getSlotName(): string
    {
        return InbreedingCoefficientProcessSlot::getName($this->getSlot());
    }

    /**
     * @param  int  $slot
     * @return InbreedingCoefficientProcess
     */
    public function setSlot(int $slot): InbreedingCoefficientProcess
    {
        $this->slot = $slot;
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
     * @return InbreedingCoefficientProcess
     */
    public function setErrorCode(?string $errorCode): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setErrorMessage(?string $errorMessage): InbreedingCoefficientProcess
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * @return string
     */
    public function getDebugErrorMessage(): string
    {
        return $this->debugErrorMessage;
    }

    /**
     * @param  string  $debugErrorMessage
     * @return InbreedingCoefficientProcess
     */
    public function setDebugErrorMessage(string $debugErrorMessage): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setStartedAt(DateTime $startedAt): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setBumpedAt(?DateTime $bumpedAt): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setFinishedAt(?DateTime $finishedAt): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setProgress(int $progress): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setNewCount(int $newCount): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setUpdatedCount(int $updatedCount): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setSkippedCount(int $skippedCount): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setProcessed(int $processed): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setTotal(int $total): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setLog(?string $log): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setRecalculate(bool $recalculate): InbreedingCoefficientProcess
    {
        $this->recalculate = $recalculate;
        return $this;
    }

    public function setProcessDetails(
        ProcessDetails $processDetails,
        bool $setFinishedAt = false
    ): InbreedingCoefficientProcess
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
        $this->setProgress(
            intval($processDetails->getProcessed() / $processDetails->getTotal())
        );
        $this->setLog($processDetails->getLogMessage());

        return $this;
    }

    /**
     * @param  bool  $isCancelled
     * @return InbreedingCoefficientProcess
     */
    public function setIsCancelled(bool $isCancelled): InbreedingCoefficientProcess
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
     * @return InbreedingCoefficientProcess
     */
    public function setIsLocked(bool $isLocked): InbreedingCoefficientProcess
    {
        $this->isLocked = $isLocked;
        return $this;
    }


}
