<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimals
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimals extends DeclareBase
{
  /**
   * @Assert\NotBlank
   * @ORM\OneToOne(targetEntity="Location")
   * @ORM\JoinColumn(name="location_id", referencedColumnName="id")
   * @JMS\Type("AppBundle\Entity\Location")
   */
  private $location;


    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return RetrieveAnimals
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
