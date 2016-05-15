<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimalDetails
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalDetailsRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimalDetails extends DeclareBase
{

}
