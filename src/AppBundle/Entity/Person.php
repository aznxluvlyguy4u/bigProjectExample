<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Person
 * @package AppBundle\Entity
 */
abstract class Person
{
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @var
   */
  protected $firstName;

  /**
   * @var
   */
  protected $lastName;

  /**
   * @var
   */
  protected $emailAddress;
}