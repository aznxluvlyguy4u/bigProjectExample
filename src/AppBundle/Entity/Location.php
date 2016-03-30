<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Location
 * @ORM\Entity(repositoryClass="AppBundle\Entity\LocationRepository")
 * @package AppBundle\Entity
 */
class Location
{
  /**
   * @var integer
   *
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @Assert\Length(max = 12)
   * @JMS\Type("string")
   */
  private $ubn;

  /**
   * @var array
   *
   * @ORM\OneToMany(targetEntity="Arrival", mappedBy="location")
   */
  protected $arrivals;

  //private $company;

  private $client;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->arrivals = new ArrayCollection();
  }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return Location
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * Add arrival
     *
     * @param \AppBundle\Entity\Arrival $arrival
     *
     * @return Location
     */
    public function addArrival(\AppBundle\Entity\Arrival $arrival)
    {
        $this->arrivals[] = $arrival;

        return $this;
    }

    /**
     * Remove arrival
     *
     * @param \AppBundle\Entity\Arrival $arrival
     */
    public function removeArrival(\AppBundle\Entity\Arrival $arrival)
    {
        $this->arrivals->removeElement($arrival);
    }

    /**
     * Get arrivals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArrivals()
    {
        return $this->arrivals;
    }
}
