<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MuscleThicknessRepository")
 * @package AppBundle\Entity
 */
class MuscleThickness extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $muscleThickness;

  /**
   * MuscleThickness constructor.
   */
    public function __construct() 
    {
      parent::__construct();
      $this->muscleThickness = 0.00;
    }

 
}
