<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class VsmIdGroup
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Entity\VsmIdGroupRepository")
 */
class VsmIdGroup
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
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false)
     * @JMS\Type("string")
     */
    private $primaryVsmId;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @ORM\Column(type="string", nullable=false, unique=true)
     * @JMS\Type("string")
     */
    private $secondaryVsmId;

    public function __construct($primaryVsmId = null, $secondaryVsmId = null)
    {
        $this->primaryVsmId = $primaryVsmId;
        $this->secondaryVsmId = $secondaryVsmId;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPrimaryVsmId()
    {
        return $this->primaryVsmId;
    }

    /**
     * @param string $primaryVsmId
     */
    public function setPrimaryVsmId($primaryVsmId)
    {
        $this->primaryVsmId = $primaryVsmId;
    }

    /**
     * @return string
     */
    public function getSecondaryVsmId()
    {
        return $this->secondaryVsmId;
    }

    /**
     * @param string $secondaryVsmId
     */
    public function setSecondaryVsmId($secondaryVsmId)
    {
        $this->secondaryVsmId = $secondaryVsmId;
    }





}