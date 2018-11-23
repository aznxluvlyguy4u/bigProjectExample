<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ExteriorBreedIndex
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ExteriorBreedIndexRepository")
 * @package AppBundle\Entity
 */
class ExteriorBreedIndex extends BreedIndex
{
    use EntityClassInfo;
}