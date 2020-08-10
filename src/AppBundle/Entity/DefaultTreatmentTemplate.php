<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DefaultTreatmentTemplate
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class DefaultTreatmentTemplate extends TreatmentTemplate implements TreatmentTemplateInterface
{
    use EntityClassInfo;

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("allow_end_date")
     * @JMS\Groups({
     *     "TREATMENT_TEMPLATE"
     * })
     */
    public function allowEndDate(): bool {
        return true;
    }

}
