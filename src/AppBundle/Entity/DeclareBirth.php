<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareBirth
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareBirthRepository")
 * @package AppBundle\Entity
 */
class DeclareBirth extends DeclareBase
{
//TODO

}