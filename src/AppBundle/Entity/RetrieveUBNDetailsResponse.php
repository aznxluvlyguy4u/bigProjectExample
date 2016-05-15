<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveUBNDetailsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveUBNDetailsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveUBNDetailsResponse extends DeclareBaseResponse
{

}
