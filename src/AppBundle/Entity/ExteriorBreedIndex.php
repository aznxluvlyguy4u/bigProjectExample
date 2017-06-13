<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ExteriorBreedIndex
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity")
 * @package AppBundle\Entity
 */
class ExteriorBreedIndex extends BreedIndex
{

}