<?php

namespace AppBundle\Entity;

use AppBundle\Component\Utils;
use AppBundle\Enumerator\TokenType;
use AppBundle\Traits\EntityClassInfo;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Person
 * @ORM\Entity(repositoryClass="AppBundle\Entity\PersonRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"Client" = "Client", "Employee" = "Employee", "Inspector" = "Inspector", "VwaEmployee" = "VwaEmployee"})
 * @JMS\Discriminator(field = "type", disabled=false, map = {
 *                        "Client" : "AppBundle\Entity\Client",
 *                      "Employee" : "AppBundle\Entity\Employee",
 *                     "Inspector" : "AppBundle\Entity\Inspector",
 *                   "VwaEmployee" : "AppBundle\Entity\VwaEmployee"},
 *     groups = {
 *     "ACTION_LOG_ADMIN",
 *     "ACTION_LOG_USER",
 *     "ANIMAL_DETAILS",
 *     "BASIC",
 *     "CONTACT_INFO",
 *     "ERROR_DETAILS",
 *     "TREATMENT_TEMPLATE",
 *     "USER_MEASUREMENT",
 *     "VWA"
 * })
 *
 * @package AppBundle\Entity
 * @ExclusionPolicy("all")
 */
abstract class Person implements UserInterface
{
    use EntityClassInfo;

  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="IDENTITY")
   * @Expose
   */
  protected $id;

  /**
   * @var string
   *
   * @ORM\Column(type="string", unique=true, nullable=true)
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ANIMAL_DETAILS",
   *     "TREATMENT_TEMPLATE",
   *     "USER_MEASUREMENT",
   *     "VWA"
   * })
   * @Expose
   */
  protected $personId;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ACTION_LOG_ADMIN",
   *     "ACTION_LOG_USER",
   *     "ANIMAL_DETAILS",
   *     "ERROR_DETAILS",
   *     "TREATMENT_TEMPLATE",
   *     "USER_MEASUREMENT",
   *     "VWA"
   * })
   * @Expose
   */
  protected $firstName;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "ACTION_LOG_ADMIN",
   *     "ACTION_LOG_USER",
   *     "ANIMAL_DETAILS",
   *     "ERROR_DETAILS",
   *     "TREATMENT_TEMPLATE",
   *     "USER_MEASUREMENT",
   *     "VWA"
   * })
   * @Expose
   */
  protected $lastName;

  /**
   * @var string
   *
   * @ORM\Column(type="string")
   * @Assert\NotBlank
   * @JMS\Type("string")
   * @JMS\Groups({
   *     "VWA"
   * })
   * @Expose
   */
  protected $emailAddress;

  /**
   * @var ArrayCollection
   *
   * @ORM\OneToMany(targetEntity="Token", mappedBy="owner", cascade={"persist", "remove"})
   * @JMS\Type("ArrayCollection<AppBundle\Entity\Token>")
   */
  protected $tokens;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default":true})
     * @JMS\Type("boolean")
     * @JMS\Groups({
     *     "ANIMAL_DETAILS",
     *     "USER_MEASUREMENT",
     *     "VWA"
     * })
     * @Expose
     */
  private $isActive;

  /**
   * @ORM\Column(type="string", length=64, nullable=false)
   *
   */
  protected $password;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   */
  protected $username;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   */
  protected $prefix;

  /**
   * @var string
   *
   * @ORM\Column(type="string", nullable=true)
   * @JMS\Type("string")
   */
  private $cellphoneNumber;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="created_by_id", referencedColumnName="id")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     * @Expose
     */
    protected $createdBy;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="edited_by_id", referencedColumnName="id")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     * @Expose
     */
    protected $editedBy;


    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(name="deleted_by_id", referencedColumnName="id")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     * @Expose
     */
    protected $deletedBy;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     * @Expose
     */
    protected $creationDate;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "DETAILS"
     * })
     * @Expose
     */
    protected $deleteDate;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     * @JMS\Groups({
     *     "VWA"
     * })
     * @Expose
     */
    protected $lastLoginDate;


    /**
     * @var string
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @JMS\Type("string")
     */
    private $passwordResetToken;


    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    protected $passwordResetTokenCreationDate;


  public function __construct($firstName = null, $lastName = null, $emailAddress = null,
                              $password = '', $username = null, $cellphoneNumber = null)
  {
    $this->tokens = new ArrayCollection();
    
    $this->setFirstName($firstName);
    $this->setLastName($lastName);
    $this->setEmailAddress($emailAddress);
    $this->setPassword($password);
    $this->setUsername($username);
    $this->setCellphoneNumber($cellphoneNumber);
    $this->setIsActive(true);
    $this->setCreationDate(new \DateTime());

    $this->setPersonId(Utils::generatePersonId());

    $this->setAccessToken(Utils::generateTokenCode());
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
    $this->firstName = trim($firstName);

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
    $this->lastName = trim($lastName);

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
    $this->emailAddress = trim(strtolower($emailAddress));

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
   * Get accessToken
   *
   * @return string
   */
  public function getAccessToken()
  {
    /** @var Token $token */
    foreach($this->tokens as $token) {
      if($token->getType() == TokenType::ACCESS) {
        return $token->getCode();
      }
    }
    //if no AccessToken was found
    return null;
  }

  /**
   * Returns the roles granted to the user.
   *
   * <code>
   * public function getRoles()
   * {
   *     return array('ROLE_USER');
   * }
   * </code>
   *
   * Alternatively, the roles might be stored on a ``roles`` property,
   * and populated in any number of different ways when the user object
   * is created.
   *
   * @return (Role|string)[] The user roles
   */
  public function getRoles()
  {
    return array('ROLE_USER');
  }

  /**
   * Returns the password used to authenticate the user.
   *
   * This should be the encoded password. On authentication, a plain-text
   * password will be salted, encoded, and then compared to this value.
   *
   * @return string The password
   */
  public function getPassword()
  {
    return $this->password;
  }

  /**
   * Returns the salt that was originally used to encode the password.
   *
   * Note: hashing the password is done by bcrypt!
   *
   * @return string|null The salt
   */
  public function getSalt() {
    //hashing the password is done by bcrypt, thus return null!
    return null;
  }

  /**
   * Returns the username used to authenticate the user.
   *
   * @return string The username
   */
  public function getUsername()
  {
    return $this->username;
  }

  /**
   * Returns the full name of the user.
   *
   * @return string The username
   */
  public function getFullName()
  {
    return StringUtil::getFullName($this->firstName, $this->lastName);
  }

  /**
   * Removes sensitive data from the user.
   *
   * This is important if, at any given point, sensitive information like
   * the plain-text password is stored on this object.
   */
  public function eraseCredentials()
  {

  }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Person
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Person
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return Person
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set accessToken
     *
     * @param string $accessToken
     *
     * @return Person
     */
    public function setAccessToken($accessToken)
    {
      /** @var Token $token */
      foreach($this->tokens as $token) {
        if($token->getType() == TokenType::ACCESS) {
          $token->setCode($accessToken);
          return;
        }
      }
      //if no AccessTokens were found
      $this->addToken(new Token(TokenType::ACCESS, $accessToken));
    }

  /**
   * @return string
   */
  public function getCellphoneNumber()
  {
    return $this->cellphoneNumber;
  }

  /**
   * @param string $cellphoneNumber
   */
  public function setCellphoneNumber($cellphoneNumber)
  {
    $this->cellphoneNumber = trim($cellphoneNumber);
  }

  /**
   * @return ArrayCollection
   */
  public function getTokens()
  {
    return $this->tokens;
  }

  /**
   * @param ArrayCollection $tokens
   */
  public function setTokens($tokens)
  {
    $this->tokens = $tokens;
  }

  /**
   * Add Token
   *
   * @param Token $token
   *
   * @return Person
   */
  public function addToken(Token $token)
  {
    $token->setOwner($this);
    $this->tokens[] = $token;

    return $this;
  }

  /**
   * Remove Token
   *
   * @param Token $token
   */
  public function removeToken(Token $token)
  {
    $this->tokens->removeElement($token);
  }

  /**
   * @return string
   */
  public function getPrefix()
  {
    return $this->prefix;
  }

  /**
   * @param string $prefix
   */
  public function setPrefix($prefix)
  {
    $this->prefix = trim($prefix);
  }

  /**
   * @return string
   */
  public function getPersonId()
  {
    return $this->personId;
  }

  /**
   * @param string $personId
   */
  public function setPersonId($personId)
  {
    $this->personId = $personId;
  }

    /**
     * @return Person
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param Person $createdBy
     * @return Person
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Person
     */
    public function getEditedBy()
    {
        return $this->editedBy;
    }

    /**
     * @param Person $editedBy
     * @return Person
     */
    public function setEditedBy($editedBy)
    {
        $this->editedBy = $editedBy;
        return $this;
    }

    /**
     * @return Person
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @param Person $deletedBy
     * @return Person
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return Person
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeleteDate()
    {
        return $this->deleteDate;
    }

    /**
     * @param \DateTime $deleteDate
     * @return Person
     */
    public function setDeleteDate($deleteDate)
    {
        $this->deleteDate = $deleteDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastLoginDate()
    {
        return $this->lastLoginDate;
    }

    /**
     * @param \DateTime $lastLoginDate
     * @return Person
     */
    public function setLastLoginDate($lastLoginDate)
    {
        $this->lastLoginDate = $lastLoginDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordResetToken()
    {
        return $this->passwordResetToken;
    }


    /**
     * @param string $passwordResetToken
     * @return Person
     */
    public function setPasswordResetToken($passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPasswordResetTokenCreationDate()
    {
        return $this->passwordResetTokenCreationDate;
    }


    /**
     * @return bool|mixed
     */
    public function getPasswordResetTokenAgeInDays()
    {
        return TimeUtil::getDaysBetween($this->getPasswordResetTokenCreationDate(), new \DateTime());
    }


    /**
     * @param \DateTime $passwordResetTokenCreationDate
     * @return Person
     */
    public function setPasswordResetTokenCreationDate($passwordResetTokenCreationDate)
    {
        $this->passwordResetTokenCreationDate = $passwordResetTokenCreationDate;
        return $this;
    }


    /**
     * @return Person
     */
    public function reactivate()
    {
        $this->setIsActive(true);
        $this->setDeletedBy(null);
        $this->setDeleteDate(null);
        $this->setEditedBy(null);
        $this->setCreationDate(new \DateTime());
        return $this;
    }
}
