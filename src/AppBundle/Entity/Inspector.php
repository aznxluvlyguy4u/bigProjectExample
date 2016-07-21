<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Inspector
 * @ORM\Entity(repositoryClass="AppBundle\Entity\InspectorRepository")
 * @package AppBundle\Entity
 */
class Inspector extends Person {

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $objectType;
  
    /**
     * Constructor
     */
    public function __construct()
    {
      //Call super constructor first
      parent::__construct();
  
      $this->objectType = "Inspector";
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Inspector
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
}
