<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimalDetailsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalDetailsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimalDetailsResponse extends DeclareBaseResponse
{

}
