<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Person
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PersonRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @package AppBundle\Entity
 */
abstract class Person
{
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $firstName;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $lastName;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $emailAddress;

  /**
   * @var string
   *
   * @ORM\Column(type="string",  unique=true)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  protected $accessToken;

  public function __construct()
  {
    $this->setAccessToken(sha1(uniqid(rand(), true)));
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
   * Set firstName
   *
   * @param string $firstName
   *
   * @return Person
   */
  public function setFirstName($firstName)
  {
    $this->firstName = $firstName;

    return $this;
  }

  /**
   * Get firstName
   *
   * @return string
   */
  public function getFirstName()
  {
    return $this->firstName;
  }

  /**
   * Set lastName
   *
   * @param string $lastName
   *
   * @return Person
   */
  public function setLastName($lastName)
  {
    $this->lastName = $lastName;

    return $this;
  }

  /**
   * Get lastName
   *
   * @return string
   */
  public function getLastName()
  {
    return $this->lastName;
  }

  /**
   * Set emailAddress
   *
   * @param string $emailAddress
   *
   * @return Person
   */
  public function setEmailAddress($emailAddress)
  {
    $this->emailAddress = $emailAddress;

    return $this;
  }

  /**
   * Get emailAddress
   *
   * @return string
   */
  public function getEmailAddress()
  {
    return $this->emailAddress;
  }


    /**
     * Set accessToken
     *
     * @param string $accessToken
     *
     * @return Person
     */
    private function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

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
}
