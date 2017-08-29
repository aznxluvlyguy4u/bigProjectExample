<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LocationAddress
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationAddressRepository")
 * @package AppBundle\Entity
 */
class LocationAddress extends Address
{
    use EntityClassInfo;
}
