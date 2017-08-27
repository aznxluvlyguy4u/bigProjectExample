<?php

namespace AppBundle\Entity;


use AppBundle\Component\Utils;
use AppBundle\Enumerator\TokenType;
use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use \DateTime;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Token
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TokenRepository")
 * @package AppBundle\Entity
 */
class Token
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string", unique=true)
     */
    private $code;
    
    /**
     * @var string
     * @JMS\Type("string")
     * @Assert\NotBlank
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     * @JMS\Type("AppBundle\Entity\Person")
     */
    private $owner;

    /**
     * @ORM\ManyToOne(targetEntity="Employee", inversedBy="ghostTokens", cascade={"persist"})
     * @ORM\JoinColumn(name="admin_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Employee")
     */
    private $admin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\Date
     * @Assert\NotBlank
     * @JMS\Type("DateTime")
     */
    private $creationDateTime;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     * @Assert\NotBlank
     * @JMS\Type("boolean")
     */
    private $isVerified;


    /**
     * Token constructor.
     * @param string $type
     * @param string $token
     */
    public function __construct($type, $token = null)
    {
        if($token == null) {
            $this->code = Utils::generateTokenCode();
        } else {
            $this->code = $token;
        }

        $this->type = $type;
        $this->creationDateTime = new \DateTime();
        $this->isVerified = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return Person
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Person $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return Employee
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @param Employee $admin
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDateTime()
    {
        return $this->creationDateTime;
    }

    /**
     * @param \DateTime $creationDateTime
     */
    public function setCreationDateTime($creationDateTime)
    {
        $this->creationDateTime = $creationDateTime;
    }

    /**
     * @return boolean
     */
    public function getIsVerified()
    {
        return $this->isVerified;
    }

    /**
     * @param boolean $isVerified
     */
    public function setIsVerified($isVerified)
    {
        $this->isVerified = $isVerified;
    }


    
}
