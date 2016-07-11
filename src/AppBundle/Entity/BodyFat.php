<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\BodyFatRepository")
 * @package AppBundle\Entity
 */
class BodyFat extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $bodyFat;
  
    public function __construct()
    {
      parent::__construct();
      $this->bodyFat = 0.00;
    }

   
}
