<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TailLengthRepository")
 * @package AppBundle\Entity
 */
class TailLength extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $tailLength;

    /**
    * TailLength constructor.
    */
    public function __construct() 
    {
      parent::__construct();
      $this->tailLength = 0.00;
    }

   
}
