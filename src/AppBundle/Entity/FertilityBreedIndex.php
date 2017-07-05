<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class FertilityBreedIndex
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FertilityBreedIndexRepository")
 * @package AppBundle\Entity
 */
class FertilityBreedIndex extends BreedIndex
{

}