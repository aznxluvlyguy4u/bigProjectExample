<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class CompanyAddress
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AddressRepository")
 * @package AppBundle\Entity
 */
class CompanyAddress extends Address
{
    use EntityClassInfo;
}
