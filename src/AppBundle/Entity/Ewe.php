<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Sheep as BaseSheep;

/**
 * Class Ewe
 * @package AppBundle\Entity
 */
class Ewe extends BaseSheep
{

  /**
   * @ORM\OneToMany(targetEntity="Child", mappedBy="parentMother")
   */
  protected $childeren;


  //
  public function __construct() {
    $this->children = new \Doctrine\Common\Collections\ArrayCollection();
  }

}