<?php

namespace AppBundle\Entity;


use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BreedCode
 * @ORM\Entity(repositoryClass="AppBundle\Entity\GenderHistoryItemRepository")
 * @package AppBundle\Entity
 */
class GenderHistoryItem
{
    use EntityClassInfo;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $logDate;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $previousGender;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $newGender;

    /**
     * @var Animal
     * @ORM\ManyToOne(targetEntity="Animal", inversedBy="genderHistory")
     * @ORM\JoinColumn(name="animal_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $animal;


    public function __construct($previousGender = null, $newGender = null)
    {
        $this->logDate = new \DateTime();

        $this->previousGender = $previousGender;
        $this->newGender = $newGender;
    }


    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return string
     */
    public function getPreviousGender()
    {
        return $this->previousGender;
    }

    /**
     * @param string $previousGender
     */
    public function setPreviousGender($previousGender)
    {
        $this->previousGender = $previousGender;
    }

    /**
     * @return string
     */
    public function getNewGender()
    {
        return $this->newGender;
    }

    /**
     * @param string $newGender
     */
    public function setNewGender($newGender)
    {
        $this->newGender = $newGender;
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
    
    


}