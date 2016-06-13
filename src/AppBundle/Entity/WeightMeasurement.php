<?php

namespace AppBundle\Entity;

use \AppBundle\Entity\Animal;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\DateTime;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class WeightMeasurement
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WeightMeasurementRepository")
 * @package AppBundle\Entity
 */
class WeightMeasurement
{

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $weightMeasurementDate;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="weightMeasurements")
     * @JMS\Type("AppBundle\Entity\Animal")
     */
    private $animal;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $weight;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return Animal
     */
    public function getAnimal()
    {
        return $this->animal;
    }

    /**
     * @param Animal $animal
     */
    public function setAnimal($animal)
    {
        $this->animal = $animal;
    }

    /**
     * @return \DateTime
     */
    public function getWeightMeasurementDate()
    {
        return $this->weightMeasurementDate;
    }

    /**
     * @param \DateTime $weightMeasurementDate
     */
    public function setWeightMeasurementDate($weightMeasurementDate)
    {
        $this->weightMeasurementDate = $weightMeasurementDate;
    }

    /**
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * @param \DateTime $logDate
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;
    }


}
