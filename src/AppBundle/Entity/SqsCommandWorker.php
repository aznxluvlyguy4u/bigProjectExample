<?php


namespace AppBundle\Entity;

use AppBundle\Enumerator\WorkerType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class SqsCommandWorker
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\SqsCommandWorkerRepository")
 * @package AppBundle\Entity
 */
class SqsCommandWorker extends Worker
{
    use EntityClassInfo;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $commandType;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $content;

    public function __construct()
    {
        parent::__construct();
        $this->setWorkerType(WorkerType::SQS_COMMAND);
    }

    /**
     * @return int
     */
    public function getCommandType(): int
    {
        return $this->commandType;
    }

    /**
     * @param int $commandType
     * @return SqsCommandWorker
     */
    public function setCommandType(int $commandType): SqsCommandWorker
    {
        $this->commandType = $commandType;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param null|string $content
     * @return SqsCommandWorker
     */
    public function setContent(?string $content): SqsCommandWorker
    {
        $this->content = $content;
        return $this;
    }



}