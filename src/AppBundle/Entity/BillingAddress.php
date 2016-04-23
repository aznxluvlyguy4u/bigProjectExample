<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BillingAddress
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AddressRepository")
 * @package AppBundle\Entity
 */
class BillingAddress extends Address {

}
