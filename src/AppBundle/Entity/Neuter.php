<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Neuter
 * @ORM\Entity(repositoryClass="AppBundle\Entity\NeuterRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Neuter extends Animal
{
    use EntityClassInfo;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "DECLARE"
     * })
     */
    private $objectType;

    /**
     * Neuter constructor.
     */
    public function __construct() {
        //Call super constructor first
        parent::__construct();

        $this->objectType = "Neuter";
        $this->setAnimalType(AnimalType::sheep);
        $this->setAnimalCategory(Constant::DEFAULT_ANIMAL_CATEGORY);
        $this->setGender(GenderType::NEUTER);
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Neuter
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * Get objectType
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    public static function getClassName() {
        return get_called_class();
    }


    /**
     * @return ArrayCollection
     */
    public function getEvents()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->orX(
                Criteria::expr()->eq('requestState', "'".RequestStateType::FINISHED."'"),
                Criteria::expr()->eq('requestState', "'".RequestStateType::FINISHED_WITH_WARNING."'")
            ))
            ->orderBy(array("logDate" => Criteria::DESC))
        ;

        return parent::getEvents()->matching($criteria);
    }
}
