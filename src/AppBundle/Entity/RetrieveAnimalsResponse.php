<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class RetrieveAnimalsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveAnimalsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveAnimalsResponse extends DeclareBaseResponse
{
  /**
   * @var RetrieveAnimals
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="RetrieveAnimals", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\RetrieveAnimals")
   */
  private $retrieveAnimalsRequestMessage;

  /**
   *
   * @var ArrayCollection
   *
   * @ORM\ManyToMany(targetEntity="Animal")
   * @ORM\JoinTable(name="retrieve_animals_response_animals_retrieved",
   *      joinColumns={@ORM\JoinColumn(name="retrieve_animal_response_id", referencedColumnName="id")},
   *      inverseJoinColumns={@ORM\JoinColumn(name="animal_id", referencedColumnName="id", unique=true)}
   * )
   * @JMS\Type("AppBundle\Entity\Animal")
   */
  private $animalsRetrieved;

  /**
   * RetrieveAnimalsResponse constructor.
   */
  public function __construct() {
    $this->animalsRetrieved = new ArrayCollection();
  }


    /**
     * Set retrieveAnimalsRequestMessage
     *
     * @param \AppBundle\Entity\RetrieveAnimals $retrieveAnimalsRequestMessage
     *
     * @return RetrieveAnimalsResponse
     */
    public function setRetrieveAnimalsRequestMessage(\AppBundle\Entity\RetrieveAnimals $retrieveAnimalsRequestMessage = null)
    {
        $this->retrieveAnimalsRequestMessage = $retrieveAnimalsRequestMessage;

        return $this;
    }

    /**
     * Get retrieveAnimalsRequestMessage
     *
     * @return \AppBundle\Entity\RetrieveAnimals
     */
    public function getRetrieveAnimalsRequestMessage()
    {
        return $this->retrieveAnimalsRequestMessage;
    }

    /**
     * Add animalsRetrieved
     *
     * @param \AppBundle\Entity\Animal $animalsRetrieved
     *
     * @return RetrieveAnimalsResponse
     */
    public function addAnimalsRetrieved(\AppBundle\Entity\Animal $animalsRetrieved)
    {
        $this->animalsRetrieved[] = $animalsRetrieved;

        return $this;
    }

    /**
     * Remove animalsRetrieved
     *
     * @param \AppBundle\Entity\Animal $animalsRetrieved
     */
    public function removeAnimalsRetrieved(\AppBundle\Entity\Animal $animalsRetrieved)
    {
        $this->animalsRetrieved->removeElement($animalsRetrieved);
    }

    /**
     * Get animalsRetrieved
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAnimalsRetrieved()
    {
        return $this->animalsRetrieved;
    }
}
