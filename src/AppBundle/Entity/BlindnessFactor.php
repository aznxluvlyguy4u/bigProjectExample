<?php


namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class BlindnessFactor
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BlindnessFactorRepository")
 * @package AppBundle\Entity
 */
class BlindnessFactor
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"}, nullable=true)
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var Animal
     *
     * @ORM\ManyToOne(targetEntity="Animal")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id")
     */
    private $animal;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $blindnessFactor;

    /**
     * BlindnessFactor constructor.
     * @param \DateTime $logDate
     * @param Animal $animal
     * @param string $blindnessFactor
     */
    public function __construct(Animal $animal, $blindnessFactor, \DateTime $logDate = null)
    {
        if($logDate == null) { $logDate = new \DateTime(); }
        $this->logDate = $logDate;
        $this->animal = $animal;
        $this->blindnessFactor = $blindnessFactor;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return string
     */
    public function getBlindnessFactor()
    {
        return $this->blindnessFactor;
    }

    /**
     * @param string $blindnessFactor
     */
    public function setBlindnessFactor($blindnessFactor)
    {
        $this->blindnessFactor = $blindnessFactor;
    }
    
    


}