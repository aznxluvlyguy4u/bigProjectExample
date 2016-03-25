<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use AppBundle\Entity\Person as BasePerson;

/**
 * Class Client
 * @package AppBundle\Entity
 */
class Client extends BasePerson
{

  /**
   * @var Location
   *
   * @ORM\OneToMany(targetEntity="Location", inversedBy="client")
   */
  private $locations;

  /**
   * @var Company
   *
   * @ORM\OneToMany(targetEntity="Location", inversedBy="company")
   */
  private $companies;

}