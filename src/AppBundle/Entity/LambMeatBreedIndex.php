<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LambMeatBreedIndex
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LambMeatBreedIndexRepository")
 * @package AppBundle\Entity
 */
class LambMeatBreedIndex extends BreedIndex
{
    use EntityClassInfo;
}