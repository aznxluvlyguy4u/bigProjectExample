<?php

namespace AppBundle\Entity;

use AppBundle\Entity\LocationHealthInspection;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Employee
 * @ORM\Entity(repositoryClass="AppBundle\Entity\EmployeeRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class Employee extends Person
{
    use EntityClassInfo;

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
     * @Expose
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CompanyNote", mappedBy="creator")
     * @JMS\Type("array")
     */
    private $notes;

    /**
     * Employee constructor.
     * @param string $accessLevel use the AccessLevelType enumerator values to set the accessLevel
     * @param string $firstName
     * @param string $lastName
     * @param string $emailAddress
     * @param string $password
     * @param string $username
     * @param string $cellphoneNumber
     */
    public function __construct($accessLevel, $firstName = null, $lastName = null, $emailAddress = null,
                              $password = '', $username = null, $cellphoneNumber = null)
    {
        //Call super constructor first
        parent::__construct($firstName, $lastName, $emailAddress, $password = '', $username, $cellphoneNumber);

        $this->accessLevel = $accessLevel;
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

    /**
     * Add ghostToken
     *
     * @param \AppBundle\Entity\Token $ghostToken
     *
     * @return Employee
     */
    public function addGhostToken(\AppBundle\Entity\Token $ghostToken)
    {
        $this->ghostTokens[] = $ghostToken;

        return $this;
    }

    /**
     * Remove ghostToken
     *
     * @param \AppBundle\Entity\Token $ghostToken
     */
    public function removeGhostToken(\AppBundle\Entity\Token $ghostToken)
    {
        $this->ghostTokens->removeElement($ghostToken);
    }

    /**
     * @return ArrayCollection
     */
    public function getHealthInspections()
    {
        return $this->healthInspections;
    }

    /**
     * @param ArrayCollection $healthInspections
     */
    public function setHealthInspections($healthInspections)
    {
        $this->healthInspections = $healthInspections;
    }

    /**
     * @return ArrayCollection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param ArrayCollection $notes
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
    }
}
