<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Company
 * @package AppBundle\Entity
 */
class Company
{
  protected $id;
  private $companyName;
  private $location;

}
