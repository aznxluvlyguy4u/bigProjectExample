<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use AppBundle\Entity\Person as BasePerson;

/**
 * Class Employee
 * @package AppBundle\Entity
 */
class Employee extends BasePerson
{

}