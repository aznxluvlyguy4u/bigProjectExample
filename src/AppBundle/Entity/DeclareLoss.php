<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class DeclareLoss
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareLoss")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class DeclareLoss extends DeclareBase
{
    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="losses", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Animal")
     * @Expose
     */
    private $animal;


    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 20)
     * @JMS\Type("string")
     * @Expose
     */
    private $reasonOfLoss;

    /**
     * Note from UXPIN:
     * "Project Manager: Dierverwerker is per definitie Rendac Son B.V. 2299077"
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max = 12)
     * @JMS\Type("string")
     * @Expose
     */
    private $ubnProcessor;

    /**
     * @ORM\OneToMany(targetEntity="DeclareLossResponse", mappedBy="declareLossRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="declare_loss_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     * @Expose
     */
    private $responses;

    /**
     * DeclareLoss constructor.
     */
    public function __construct() {
        parent::__construct();

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\DeclareLossResponse $response
     *
     * @return DeclareLoss
     */
    public function addResponse(\AppBundle\Entity\DeclareLossResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\DeclareLossResponse $response
     */
    public function removeResponse(\AppBundle\Entity\DeclareLossResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Set animal
     *
     * @param \AppBundle\Entity\Animal $animal
     *
     * @return DeclareLoss
     */
    public function setAnimal(\AppBundle\Entity\Animal $animal = null)
    {
        $this->animal = $animal;

        return $this;
    }

    /**
     * Get animal
     *
     * @return \AppBundle\Entity\Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareLoss
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
     * @return string
     */
    public function getReasonOfLoss()
    {
        return $this->reasonOfLoss;
    }

    /**
     * @param string $reasonOfLoss
     */
    public function setReasonOfLoss($reasonOfLoss)
    {
        $this->reasonOfLoss = $reasonOfLoss;
    }

    /**
     * @return string
     */
    public function getUbnProcessor()
    {
        return $this->ubnProcessor;
    }

    /**
     * @param string $ubnProcessor
     */
    public function setUbnProcessor($ubnProcessor)
    {
        $this->ubnProcessor = $ubnProcessor;
    }
}
