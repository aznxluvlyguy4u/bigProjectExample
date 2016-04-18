<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Employee
 * @ORM\Entity
 * @package AppBundle\Entity
 */
class Employee extends Person
{

}
