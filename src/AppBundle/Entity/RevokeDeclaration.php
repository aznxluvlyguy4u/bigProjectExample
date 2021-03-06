<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class RevokeDeclaration
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RevokeDeclarationRepository")
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
class RevokeDeclaration extends DeclareBase
{
    use EntityClassInfo;

    const MESSAGE_NUMBER_MAX_COUNT = 15;

    /**
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="revokes", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\Location")
     * @JMS\Groups({
     *     "RESPONSE_PERSISTENCE"
     * })
     * @Expose
     *
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity="RevokeDeclarationResponse", mappedBy="revokeDeclarationRequestMessage", cascade={"persist"})
     * @ORM\JoinColumn(name="revoke_declaration_request_message_id", referencedColumnName="id")
     * @ORM\OrderBy({"logDate" = "ASC"})
     * @JMS\Type("ArrayCollection<AppBundle\Entity\RevokeDeclarationResponse>")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
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
     * @var DeclareExport
     *
     * @ORM\OneToOne(targetEntity="DeclareExport", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareExport")
     * @Expose
     */
    private $export;

    /**
     * @var DeclareLoss
     *
     * @ORM\OneToOne(targetEntity="DeclareLoss", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareLoss")
     * @Expose
     */
    private $loss;

    /**
     * @var DeclareTagReplace
     *
     * @ORM\OneToOne(targetEntity="DeclareTagReplace", mappedBy="revoke", cascade={"persist"})
     * @JMS\Type("AppBundle\Entity\DeclareTagReplace")
     * @Expose
     */
    private $tagReplace;

    /**
     * @ORM\Column(type="string")
     * @Assert\Length(max = 15)
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS",
     *     "RVO"
     * })
     * @Expose
     */
    private $messageNumber;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $requestIdToRevoke;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     * @JMS\Groups({
     *     "ERROR_DETAILS"
     * })
     * @Expose
     */
    private $requestTypeToRevoke;
    
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
     * @return DeclareExport
     */
    public function getExport()
    {
        return $this->export;
    }

    /**
     * @param DeclareExport $export
     */
    public function setExport($export = null)
    {
        $this->export = $export;
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



    /**
     * Set location
     *
     * @param \AppBundle\Entity\Location $location
     *
     * @return RevokeDeclaration
     */
    public function setLocation(\AppBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \AppBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }


    /**
     * Set requestIdToRevoke
     *
     * @param string $requestIdToRevoke
     *
     * @return RevokeDeclaration
     */
    public function setRequestIdToRevoke($requestIdToRevoke)
    {
        $this->requestIdToRevoke = $requestIdToRevoke;

        return $this;
    }

    /**
     * Get requestIdToRevoke
     *
     * @return string
     */
    public function getRequestIdToRevoke()
    {
        return $this->requestIdToRevoke;
    }

    /**
     * Set requestTypeToRevoke
     *
     * @param string $requestTypeToRevoke
     *
     * @return RevokeDeclaration
     */
    public function setRequestTypeToRevoke($requestTypeToRevoke)
    {
        $this->requestTypeToRevoke = $requestTypeToRevoke;

        return $this;
    }

    /**
     * Get requestTypeToRevoke
     *
     * @return string
     */
    public function getRequestTypeToRevoke()
    {
        return $this->requestTypeToRevoke;
    }

    /**
     * Set tagReplace
     *
     * @param \AppBundle\Entity\DeclareTagReplace $tagReplace
     *
     * @return RevokeDeclaration
     */
    public function setTagReplace(\AppBundle\Entity\DeclareTagReplace $tagReplace = null)
    {
        $this->tagReplace = $tagReplace;

        return $this;
    }

    /**
     * Get tagReplace
     *
     * @return \AppBundle\Entity\DeclareTagReplace
     */
    public function getTagReplace()
    {
        return $this->tagReplace;
    }
}
