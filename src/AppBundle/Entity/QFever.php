<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class QFever
 * @package AppBundle\Entity
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\QFeverRepository")
 */
class QFever extends TreatmentTemplate
{
    use EntityClassInfo;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $qFeverType;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $flagType;

    /**
     * @return string
     */
    public function getQFeverType(): string
    {
        return $this->qFeverType;
    }

    /**
     * @param  string  $qFeverType
     * @return QFever
     */
    public function setQFeverType(string $qFeverType): QFever
    {
        $this->qFeverType = $qFeverType;
        return $this;
    }

    /**
     * @return string
     */
    public function getFlagType(): string
    {
        return $this->flagType;
    }

    /**
     * @param string $flagType
     * @return QFever
     */
    public function setFlagType(string $flagType): self
    {
        $this->flagType = $flagType;

        return $this;
    }
}
