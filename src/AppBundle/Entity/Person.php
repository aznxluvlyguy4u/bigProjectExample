<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Person
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PersonRepository")
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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
