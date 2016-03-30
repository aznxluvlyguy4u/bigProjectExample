<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Ram
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RamRepository")
 * @package AppBundle\Entity
 */
class Ram extends Animal
{
  /**
   * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentFather")
   * @JMS\Type("AppBundle\Entity\Ram")
   */
  protected $childeren;


  /**
   * Ram constructor.
   */
  public function __construct() {
    //Call super constructor first
    parent::__construct();

    //Create childrens array
    $this->children = new ArrayCollection();
  }

    /**
     * Add childeren
     *
     * @param \AppBundle\Entity\Animal $childeren
     *
     * @return Ram
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
