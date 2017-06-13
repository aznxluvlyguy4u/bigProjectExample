<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class WormResistanceBreedIndex
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity")
 * @package AppBundle\Entity
 */
class WormResistanceBreedIndex extends BreedIndex
{

}