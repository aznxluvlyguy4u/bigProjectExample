<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class WormResistanceBreedIndex
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WormResistanceBreedIndexRepository")
 * @package AppBundle\Entity
 */
class WormResistanceBreedIndex extends BreedIndex
{
    use EntityClassInfo;
}