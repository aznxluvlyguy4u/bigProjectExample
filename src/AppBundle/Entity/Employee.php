<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Employee
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EmployeeRepository")
 * @package AppBundle\Entity
 */
class Employee extends Person
{

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
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     * @JMS\Type("string")
     */
    private $accessLevel;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Token", mappedBy="admin", cascade={"persist"})
     * @JMS\Type("array")
     */
    private $ghostTokens;

    /**
     * Constructor
     */
    public function __construct()
    {
        //Call super constructor first
        parent::__construct();

        $this->objectType = "Employee";
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return Employee
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set objectType
     *
     * @param string $objectType
     *
     * @return Employee
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
     * Set accessToken
     *
     * @param string $accessToken
     *
     * @return Employee
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessLevel()
    {
        return $this->accessLevel;
    }

    /**
     * @param string $accessLevel
     */
    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;
    }

    /**
     * @return ArrayCollection
     */
    public function getGhostTokens()
    {
        return $this->ghostTokens;
    }

    /**
     * @param ArrayCollection $ghostTokens
     */
    public function setGhostTokens($ghostTokens)
    {
        $this->ghostTokens = $ghostTokens;
    }

    

}
