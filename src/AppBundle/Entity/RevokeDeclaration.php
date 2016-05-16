<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class RevokeDeclaration
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RevokeDeclarationRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class RevokeDeclaration extends DeclareBase
{

    /**
     * @ORM\OneToMany(targetEntity="RevokeDeclarationResponse", mappedBy="revokeDeclarationRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_declaration_request_message_id", referencedColumnName="id")
     * @JMS\Type("array")
     * @Expose
     */
    private $responses;

    /**
     * @var DeclareArrival
     *
     * @ORM\OneToOne(targetEntity="DeclareArrival", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareArrival")
     * @Expose
     */
    private $arrival;

    /**
     * @var DeclareBirth
     *
     * @ORM\OneToOne(targetEntity="DeclareBirth", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareBirth")
     * @Expose
     */
    private $birth;

    /**
     * @var DeclareDepart
     *
     * @ORM\OneToOne(targetEntity="DeclareDepart", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareDepart")
     * @Expose
     */
    private $depart;

    /**
     * @var DeclareImport
     *
     * @ORM\OneToOne(targetEntity="DeclareImport", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareImport")
     * @Expose
     */
    private $import;

    /**
     * @var DeclareLoss
     *
     * @ORM\OneToOne(targetEntity="DeclareLoss", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareLoss")
     * @Expose
     */
    private $loss;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 15)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @Expose
     */
    protected $messageNumber;

    /**
     * constructor.
     */
    public function __construct() {
        parent::__construct();

        //Create responses array
        $this->responses = new ArrayCollection();
    }

    /**
     * @return DeclareArrival
     */
    public function getArrival()
    {
        return $this->arrival;
    }

    /**
     * @param DeclareArrival $arrival
     */
    public function setArrival($arrival = null)
    {
        $this->arrival = $arrival;
    }

    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return RevokeDeclaration
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }

    /**
     * Add response
     *
     * @param \AppBundle\Entity\RevokeDeclarationResponse $response
     *
     * @return RevokeDeclaration
     */
    public function addResponse(\AppBundle\Entity\RevokeDeclarationResponse $response)
    {
        $this->responses[] = $response;

        return $this;
    }

    /**
     * Remove response
     *
     * @param \AppBundle\Entity\RevokeDeclarationResponse $response
     */
    public function removeResponse(\AppBundle\Entity\RevokeDeclarationResponse $response)
    {
        $this->responses->removeElement($response);
    }

    /**
     * Get responses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * @return DeclareBirth
     */
    public function getBirth()
    {
        return $this->birth;
    }

    /**
     * @param DeclareBirth $birth
     */
    public function setBirth($birth = null)
    {
        $this->birth = $birth;
    }

    /**
     * @return DeclareDepart
     */
    public function getDepart()
    {
        return $this->depart;
    }

    /**
     * @param DeclareDepart $depart
     */
    public function setDepart($depart = null)
    {
        $this->depart = $depart;
    }

    /**
     * @return DeclareImport
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @param DeclareImport $import
     */
    public function setImport($import = null)
    {
        $this->import = $import;
    }

    /**
     * @return DeclareLoss
     */
    public function getLoss()
    {
        return $this->loss;
    }

    /**
     * @param DeclareLoss $loss
     */
    public function setLoss($loss = null)
    {
        $this->loss = $loss;
    }

    /**
     * @return string
     */
    public function getMessageNumber()
    {
        return $this->messageNumber;
    }

    /**
     * @param string $messageNumber
     */
    public function setMessageNumber($messageNumber)
    {
        $this->messageNumber = $messageNumber;
    }


}
