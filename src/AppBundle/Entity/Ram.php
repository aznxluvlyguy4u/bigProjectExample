<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use AppBundle\Entity\Sheep as BaseSheep;


/**
 * Class Ram
 * @ORM\Entity(repositoryClass="AppBundle\Entity\AnimalRepository")
 * @package AppBundle\Entity
 */
class Ram extends BaseSheep
{
  /**
   * @ORM\OneToMany(targetEntity="Animal", mappedBy="parentFather")
   */
  protected $childeren;


  //
  public function __construct() {
    $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
