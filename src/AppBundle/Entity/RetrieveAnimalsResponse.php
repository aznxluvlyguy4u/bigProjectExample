<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimalsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimalsResponse extends DeclareBaseResponse
{

}
