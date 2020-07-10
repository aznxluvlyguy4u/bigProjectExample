<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class DefaultTreatmentTemplate
 * @package AppBundle\Entity
 * @ORM\Entity()
 */
class DefaultTreatmentTemplate extends TreatmentTemplate
{
    use EntityClassInfo;
}
