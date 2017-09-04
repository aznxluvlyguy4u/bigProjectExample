<?php


namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

use AppBundle\Traits\EntityClassInfo;

/**
 * Class VwaEmployee
 *
 * @ORM\Entity(repositoryClass="AppBundle\Entity\VwaEmployeeRepository")
 * @package AppBundle\Entity
 */
class VwaEmployee extends Person
{
    use EntityClassInfo;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $objectType;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({"VWA"})
     */
    private $invitationDate;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="invited_by_id", referencedColumnName="id")
     * @JMS\Groups({"DETAILS"})
     */
    private $invitedBy;


    /**
     * Constructor
     */
    public function __construct()
    {
        //Call super constructor first
        parent::__construct();

        $this->objectType = "VwaEmployee";
    }


    /**
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }


    /**
     * @param string $objectType
     * @return VwaEmployee
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getInvitationDate()
    {
        return $this->invitationDate;
    }

    /**
     * @param \DateTime $invitationDate
     * @return VwaEmployee
     */
    public function setInvitationDate($invitationDate)
    {
        $this->invitationDate = $invitationDate;
        return $this;
    }

    /**
     * @return Person
     */
    public function getInvitedBy()
    {
        return $this->invitedBy;
    }

    /**
     * @param Person $invitedBy
     * @return VwaEmployee
     */
    public function setInvitedBy($invitedBy)
    {
        $this->invitedBy = $invitedBy;
        return $this;
    }



}