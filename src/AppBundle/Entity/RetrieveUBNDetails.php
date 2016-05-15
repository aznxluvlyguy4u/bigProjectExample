<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveUBNDetails
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveUBNDetailsRepository")
 * @package AppBundle\Entity
 */
class RetrieveUBNDetails extends DeclareBase
{

}
