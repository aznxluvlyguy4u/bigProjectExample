<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity
 */
class Animal
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\Regex("/([A-Z]{2}[0-9]{12})\b/")
     * @JMS\Type("string")
     */
    protected $uln;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @ORM\Column(type="date")
     * @Assert\Date()
     * @JMS\Type("DateTime")
     */
    protected $date_of_birth;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uln.
     *
     * @param string $uln
     *
     * @return Animal
     */
    public function setUln($uln)
    {
        $this->uln = $uln;

        return $this;
    }

    /**
     * Get uln.
     *
     * @return string
     */
    public function getUln()
    {
        return $this->uln;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Animal
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateOfBirth.
     *
     * @param \DateTime $dateOfBirth
     *
     * @return Animal
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->date_of_birth = $dateOfBirth;

        return $this;
    }

    /**
     * Get dateOfBirth.
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->date_of_birth;
    }
}
