<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class BillingAddress
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AddressRepository")
 * @package AppBundle\Entity
 */
class BillingAddress extends Address
{
    use EntityClassInfo;

}
