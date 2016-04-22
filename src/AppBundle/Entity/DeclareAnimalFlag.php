<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareAnimalFlag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareAnimalFlagRepository")
 * @package AppBundle\Entity
 */
class DeclareAnimalFlag extends DeclareBase
{
//TODO

}