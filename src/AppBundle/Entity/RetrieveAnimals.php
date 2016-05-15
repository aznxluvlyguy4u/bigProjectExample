<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimals
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimals extends DeclareBase
{

}
