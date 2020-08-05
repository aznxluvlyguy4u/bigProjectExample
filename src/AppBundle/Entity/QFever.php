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
class QFever extends TreatmentTemplate implements TreatmentTemplateInterface
{
    use EntityClassInfo;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    private $qFeverType;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("allow_end_date")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    public function allowEndDate(): bool {
        return false;
    }

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
}
