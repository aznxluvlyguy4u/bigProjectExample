<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use AppBundle\Entity\Animal as BaseAnimal;

/**
 * Class Child
 * @package AppBundle\Entity
 */
class Child extends BaseAnimal
{

  /**
   *
   * @ORM\ManyToOne(targetEntity="Animal", inversedBy="parentFather")
   */
  protected $parentFather;


  /**
   * @ORM\ManyToOne(targetEntity="Animal", inversedBy="parentMother")
   */
  protected $parentMother;
}