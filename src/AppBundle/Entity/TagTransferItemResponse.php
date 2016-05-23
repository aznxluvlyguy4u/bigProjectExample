<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Tag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TagTransferItemResponseRepository")
 * @package AppBundle\Entity
 */
class TagTransferItemResponse {

  /**
   * @var integer
   *
   * @ORM\Id
   * @ORM\Column(type="integer")
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @var integer
   * @ORM\Column(type="integer")
   * @Assert\NotBlank
   */
  private $animalType;

  /**
   * @var string
   * @JMS\Type("string")
   * @ORM\Column(type="string", nullable=false)
   */
  private $ulnNumber;

  /**
   * @var string
   * @JMS\Type("string")
   * @ORM\Column(type="string", nullable=false)
   */
  private $ulnCountryCode;

  /**
   * @var string
   * @JMS\Type("string")
   * @ORM\Column(type="string", nullable=true)
   */
  private $animalOrderNumber;

  /**
   * @var string;
   *
   * @ORM\Column(type="string", nullable=true)
   *
   */
  private $errorCode;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   */
  private $errorMessage;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 1)
   */
  private $errorKindIndicator;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 1)
   */
  private $successIndicator;

  /**
   * @ORM\Column(type="string", nullable=true)
   * @Assert\Length(max = 15)
   * @JMS\Type("string")
   */
  protected $messageNumber;

  function __construct()
  {

  }

  /**
   * Get id
   *
   * @return integer
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set animalType
   *
   * @param integer $animalType
   *
   * @return TagTransferItemResponse
   */
  public function setAnimalType($animalType)
  {
    $this->animalType = $animalType;

    return $this;
  }

  /**
   * Get animalType
   *
   * @return integer
   */
  public function getAnimalType()
  {
    return $this->animalType;
  }

  /**
   * Set ulnNumber
   *
   * @param string $ulnNumber
   *
   * @return TagTransferItemResponse
   */
  public function setUlnNumber($ulnNumber)
  {
    $this->ulnNumber = $ulnNumber;

    return $this;
  }

  /**
   * Get ulnNumber
   *
   * @return string
   */
  public function getUlnNumber()
  {
    return $this->ulnNumber;
  }

  /**
   * Set ulnCountryCode
   *
   * @param string $ulnCountryCode
   *
   * @return TagTransferItemResponse
   */
  public function setUlnCountryCode($ulnCountryCode)
  {
    $this->ulnCountryCode = $ulnCountryCode;

    return $this;
  }

  /**
   * Get ulnCountryCode
   *
   * @return string
   */
  public function getUlnCountryCode()
  {
    return $this->ulnCountryCode;
  }

  /**
   * Set animalOrderNumber
   *
   * @param string $animalOrderNumber
   *
   * @return TagTransferItemResponse
   */
  public function setAnimalOrderNumber($animalOrderNumber)
  {
    $this->animalOrderNumber = $animalOrderNumber;

    return $this;
  }

  /**
   * Get animalOrderNumber
   *
   * @return string
   */
  public function getAnimalOrderNumber()
  {
    return $this->animalOrderNumber;
  }

  /**
   * Set errorCode
   *
   * @param string $errorCode
   *
   * @return TagTransferItemResponse
   */
  public function setErrorCode($errorCode)
  {
    $this->errorCode = $errorCode;

    return $this;
  }

  /**
   * Get errorCode
   *
   * @return string
   */
  public function getErrorCode()
  {
    return $this->errorCode;
  }

  /**
   * Set errorMessage
   *
   * @param string $errorMessage
   *
   * @return TagTransferItemResponse
   */
  public function setErrorMessage($errorMessage)
  {
    $this->errorMessage = $errorMessage;

    return $this;
  }

  /**
   * Get errorMessage
   *
   * @return string
   */
  public function getErrorMessage()
  {
    return $this->errorMessage;
  }

  /**
   * Set errorKindIndicator
   *
   * @param string $errorKindIndicator
   *
   * @return TagTransferItemResponse
   */
  public function setErrorKindIndicator($errorKindIndicator)
  {
    $this->errorKindIndicator = $errorKindIndicator;

    return $this;
  }

  /**
   * Get errorKindIndicator
   *
   * @return string
   */
  public function getErrorKindIndicator()
  {
    return $this->errorKindIndicator;
  }

  /**
   * Set successIndicator
   *
   * @param string $successIndicator
   *
   * @return TagTransferItemResponse
   */
  public function setSuccessIndicator($successIndicator)
  {
    $this->successIndicator = $successIndicator;

    return $this;
  }

  /**
   * Get successIndicator
   *
   * @return string
   */
  public function getSuccessIndicator()
  {
    return $this->successIndicator;
  }

  /**
   * Set messageNumber
   *
   * @param string $messageNumber
   *
   * @return TagTransferItemResponse
   */
  public function setMessageNumber($messageNumber)
  {
    $this->messageNumber = $messageNumber;

    return $this;
  }

  /**
   * Get messageNumber
   *
   * @return string
   */
  public function getMessageNumber()
  {
    return $this->messageNumber;
  }
}
