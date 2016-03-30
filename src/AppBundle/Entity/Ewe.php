<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Ewe
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EweRepository")
 * @package AppBundle\Entity
 */
class Ewe extends Animal
{

  /**
   * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentMother")
   * @JMS\Type("AppBundle\Entity\Ewe")
   */
  protected $childeren;

  /**
   * Ewe constructor.
   */
  public function __construct() {
    //Call super constructor first
    parent::__construct();

    $this->children = new ArrayCollection();
  }

    /**
     * Add childeren
     *
     * @param \AppBundle\Entity\Animal $childeren
     *
     * @return Ewe
     */
    public function addChilderen(\AppBundle\Entity\Animal $childeren)
    {
        $this->childeren[] = $childeren;

        return $this;
    }

    /**
     * Remove childeren
     *
     * @param \AppBundle\Entity\Animal $childeren
     */
    public function removeChilderen(\AppBundle\Entity\Animal $childeren)
    {
        $this->childeren->removeElement($childeren);
    }

    /**
     * Get childeren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChilderen()
    {
        return $this->childeren;
    }
}
