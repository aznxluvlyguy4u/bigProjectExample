<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Genetic
 * @ORM\Entity(repositoryClass="AppBundle\Entity\WeightRepository")
 * @package AppBundle\Entity
 */
class Weight extends Measurement {

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     * @JMS\Type("float")
     * @Assert\NotBlank
     */
    private $weight;

  /**
   * Weight constructor.
   */
    public function __construct() 
    {
      parent::__construct();
      $this->weight = 0.00;
    }
}
