<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Measurement
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MeasurementRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"BodyFat" = "BodyFat",
 *                        "Fat1" = "Fat1",
 *                        "Fat2" = "Fat2",
 *                        "Fat3" = "Fat3",
 *                        "MuscleThickness" = "MuscleThickness",
 *                        "TailLength" = "TailLength",
 *                        "Weight" = "Weight",
 *                        "PerformanceMeasurement" = "PerformanceMeasurement",
 *                        "Exterior" = "Exterior"
 * })
 * @package AppBundle\Entity
 */
abstract class Measurement {

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    protected $measurementDate;


    /**
     * @ORM\ManyToOne(targetEntity="Inspector")
     * @ORM\JoinColumn(name="inspector_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Inspector")
     */
    protected $inspector;

    /**
    * Measurement constructor.
    */
    public function __construct() {
      $this->logDate = new \DateTime();
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
     * Set logDate
     *
     * @param \DateTime $logDate
     *
     * @return Measurement
     */
    public function setLogDate($logDate)
    {
        $this->logDate = $logDate;

        return $this;
    }

    /**
     * Get logDate
     *
     * @return \DateTime
     */
    public function getLogDate()
    {
        return $this->logDate;
    }

    /**
     * Set measurementDate
     *
     * @param \DateTime $measurementDate
     *
     * @return Measurement
     */
    public function setMeasurementDate($measurementDate)
    {
        $this->measurementDate = $measurementDate;

        return $this;
    }

    /**
     * Get measurementDate
     *
     * @return \DateTime
     */
    public function getMeasurementDate()
    {
        return $this->measurementDate;
    }

    /**
     * Set inspector
     *
     * @param \AppBundle\Entity\Inspector $inspector
     *
     * @return Measurement
     */
    public function setInspector(\AppBundle\Entity\Inspector $inspector = null)
    {
        $this->inspector = $inspector;

        return $this;
    }

    /**
     * Get inspector
     *
     * @return \AppBundle\Entity\Inspector
     */
    public function getInspector()
    {
        return $this->inspector;
    }
}
