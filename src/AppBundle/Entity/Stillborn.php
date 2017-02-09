<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\GenderType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Stillborn
 * @ORM\Entity(repositoryClass="AppBundle\Entity\StillbornRepository")
 * @package AppBundle\Entity
 */
class Stillborn {

    /**
     * @var integer
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Litter
     * @JMS\Type("AppBundle\Entity\Litter")
     * @ORM\ManyToOne(targetEntity="Litter", inversedBy="stillborns", cascade={"persist"})
     * @ORM\JoinColumn(name="litter_id", referencedColumnName="id")
     */
    private $litter;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @JMS\Groups({"declare"})
     */
    private $gender;

    /**
     * @var string
     * @JMS\Type("string")
     * @ORM\Column(type="string", nullable=true)
     */
    private $birthProgress;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     */
    private $weight;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @JMS\Type("float")
     */
    private $tailLength;

    function __construct() {
      $this->gender = GenderType::NEUTER;
    }

    /**
     * @return int
     */
    public function getId() {
      return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
      $this->id = $id;
    }

    /**
     * @return string
     */
    public function getBirthProgress() {
      return $this->birthProgress;
    }

    /**
     * @param string $birthProgress
     */
    public function setBirthProgress($birthProgress) {
      $this->birthProgress = $birthProgress;
    }

    /**
     * @return string
     */
    public function getGender() {
      return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender) {
      $this->gender = $gender;
    }

    /**
     * @return Litter
     */
    public function getLitter() {
      return $this->litter;
    }

    /**
     * @param Litter $litter
     */
    public function setLitter($litter) {
      $this->litter = $litter;
    }

    /**
     * @return float
     */
    public function getTailLength() {
      return $this->tailLength;
    }

    /**
     * @param float $tailLength
     */
    public function setTailLength($tailLength) {
      $this->tailLength = $tailLength;
    }

    /**
     * @return float
     */
    public function getWeight() {
      return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight) {
      $this->weight = $weight;
    }
}