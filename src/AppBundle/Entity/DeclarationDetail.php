<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclarationDetail
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclarationDetailRepository")
 * @package AppBundle\Entity
 */
class DeclarationDetail extends DeclarationBase
{

}
