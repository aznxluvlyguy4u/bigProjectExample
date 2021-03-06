<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * Class Measurement
 *
 * @ORM\Table(indexes={@ORM\Index(name="animal_id_and_date_idx", columns={"animal_id_and_date"})})
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
 *                        "ScanMeasurementSet" = "ScanMeasurementSet",
 *                        "Exterior" = "Exterior"
 * })
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                      "BodyFat" : "AppBundle\Entity\BodyFat",
 *                         "Fat1" : "AppBundle\Entity\Fat1",
 *                         "Fat2" : "AppBundle\Entity\Fat2",
 *                         "Fat3" : "AppBundle\Entity\Fat3",
 *              "MuscleThickness" : "AppBundle\Entity\MuscleThickness",
 *                   "TailLength" : "AppBundle\Entity\TailLength",
 *                       "Weight" : "AppBundle\Entity\Weight",
 *           "ScanMeasurementSet" : "AppBundle\Entity\ScanMeasurementSet",
 *                     "Exterior" : "AppBundle\Entity\Exterior"},
 *     groups = {
 *     "BASIC",
 *     "USER_MEASUREMENT"
 * })
 * @package AppBundle\Entity
 */
abstract class Measurement
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $logDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $measurementDate;


    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    protected $animalIdAndDate;


    /**
     * @var Inspector
     *
     * @ORM\ManyToOne(targetEntity="Inspector")
     * @ORM\JoinColumn(name="inspector_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Inspector")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $inspector;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="action_by_id", referencedColumnName="id")
     */
    protected $actionBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $editDate;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="deleted_by_id", referencedColumnName="id")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $deletedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $deleteDate;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "USER_MEASUREMENT"
     * })
     */
    protected $isActive;

    /**
    * Measurement constructor.
    */
    public function __construct() {
      $this->logDate = new \DateTime();
      $this->setIsActive(true);
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

    /**
     * @return int|null
     */
    public function getInspectorId(): ?int
    {
        return $this->inspector ? $this->inspector->getId() : null;
    }

    /**
     * @return string
     */
    public function getAnimalIdAndDate()
    {
        return $this->animalIdAndDate;
    }

    /**
     * @param string $animalIdAndDate
     * @return Measurement
     */
    public function setAnimalIdAndDate($animalIdAndDate)
    {
        $this->animalIdAndDate = $animalIdAndDate;
        return $this;
    }

    /**
     * @param Animal $animal
     * @param \DateTime $measurementDate
     */
    public function setAnimalIdAndDateByAnimalAndDateTime(Animal $animal, \DateTime $measurementDate)
    {
        $this->animalIdAndDate = self::generateAnimalIdAndDate($animal, $measurementDate);
    }


    /**
     * @param Animal $animal
     * @param \DateTime $measurementDate
     * @return null|string
     */
    public static function generateAnimalIdAndDate(Animal $animal, \DateTime $measurementDate)
    {
        $animalIdAndDate = null;
        if($animal instanceof Animal) {
            $animalId = $animal->getId();
            if(is_int($animalId) && $animalId != 0) {
                $dateTimeString = $measurementDate->format('Y-m-d');
                $animalIdAndDate = $animalId.'_'.$dateTimeString;
            }
        }
        return $animalIdAndDate;
    }


    /**
     * @return Client|Employee
     */
    public function getActionBy()
    {
        return $this->actionBy;
    }

    /**
     * @param Person $actionBy
     * @param Measurement
     */
    public function setActionBy($actionBy)
    {
        $this->actionBy = $actionBy;
        return $this;
    }

    /**
     * Set editDate
     *
     * @param \DateTime $editDate
     * @return Measurement
     */
    public function setEditDate($editDate)
    {
        $this->editDate = $editDate;
        return $this;
    }

    /**
     * Get editDate
     *
     * @return \DateTime
     */
    public function getEditDate()
    {
        return $this->editDate;
    }

    /**
     * @return Person
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @param Person $deletedBy
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
    }

    /**
     * @return \DateTime
     */
    public function getDeleteDate()
    {
        return $this->deleteDate;
    }

    /**
     * @param \DateTime $deleteDate
     */
    public function setDeleteDate($deleteDate)
    {
        $this->deleteDate = $deleteDate;
    }

    /**
     * @return boolean
     */
    public function isIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return Measurement|Exterior
     *
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }


    /**
     * @param null $nullReplacement
     * @return null|string
     */
    public function getInspectorFullName($nullReplacement = null)
    {
        return $this->inspector !== null ? $this->inspector->getFullName() : $nullReplacement;
    }


}
