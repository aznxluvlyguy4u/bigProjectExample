<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use AppBundle\Entity\Animal as BaseAnimal;

/**
 * Class Goat
 * @package AppBundle\Entity
 */
class Goat extends BaseAnimal
{

}