<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\WorkerType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ReportWorker
 * @ORM\Entity(repositoryClass="AppBundle\Entity\UpdateAnimalDataWorkerRepository")
 * @package AppBundle\Entity
 */
class UpdateAnimalDataWorker extends Worker
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
    private $updateType;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Groups({
     *     "BASIC"
     * })
     */
    private $hash;

    public function __construct()
    {
        parent::__construct();
        $this->setWorkerType(WorkerType::UPDATE_ANIMAL_DATA);
    }

    /**
     * @return int
     */
    public function getUpdateType(): int
    {
        return $this->updateType;
    }

    /**
     * @param int $updateType
     * @return UpdateAnimalDataWorker
     */
    public function setUpdateType(int $updateType): UpdateAnimalDataWorker
    {
        $this->updateType = $updateType;
        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param $hash
     * @return UpdateAnimalDataWorker
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }


}