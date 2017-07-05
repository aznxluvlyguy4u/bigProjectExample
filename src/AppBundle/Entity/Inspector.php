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
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    private $inspectorCode;

    /**
     * @var boolean
     * @Assert\NotBlank
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     * @JMS\Type("boolean")
     */
    private $isAuthorizedNsfoInspector;
  
    /**
     * Constructor
     */
    public function __construct()
    {
      //Call super constructor first
      parent::__construct();
  
      $this->objectType = "Inspector";
      $this->isAuthorizedNsfoInspector = false;
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

    /**
     * @return string
     */
    public function getInspectorCode()
    {
        return $this->inspectorCode;
    }

    /**
     * @param string $inspectorCode
     * @return Inspector
     */
    public function setInspectorCode($inspectorCode)
    {
        $this->inspectorCode = $inspectorCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorizedNsfoInspector()
    {
        return $this->isAuthorizedNsfoInspector;
    }

    /**
     * @param bool $isAuthorizedNsfoInspector
     * @return Inspector
     */
    public function setIsAuthorizedNsfoInspector($isAuthorizedNsfoInspector)
    {
        $this->isAuthorizedNsfoInspector = $isAuthorizedNsfoInspector;
        return $this;
    }
    

}
