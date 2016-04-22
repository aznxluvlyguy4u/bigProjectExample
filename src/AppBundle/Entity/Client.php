<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Client
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ClientRepository")
 * @package AppBundle\Entity
 */
class Client extends Person
{

  /**
   * @ORM\Column(type="string")
   * @Assert\Length(max = 20)
   * @Assert\NotBlank
   * @JMS\Type("string")
   */
  private $relationNumberKeeper;

  //private $companies;

  /**
   * Constructor
   */
  public function __construct()
  {
    //Call super constructor first
    parent::__construct();
  }


  /**
   * Set relationNumberKeeper
   *
   * @param string $relationNumberKeeper
   *
   * @return Client
   */
  public function setRelationNumberKeeper($relationNumberKeeper)
  {
    $this->relationNumberKeeper = $relationNumberKeeper;

    return $this;
  }

  /**
   * Get relationNumberKeeper
   *
   * @return string
   */
  public function getRelationNumberKeeper()
  {
    return $this->relationNumberKeeper;
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
     * Set accessToken
     *
     * @param string $accessToken
     *
     * @return Client
     */
    public function setAccessToken($accessToken)
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
