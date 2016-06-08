<?php

namespace AppBundle\Entity;


use AppBundle\Entity\Client;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ClientMigration
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ClientMigrationRepository")
 * @package AppBundle\Entity
 */
class ClientMigration
{
    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $unencryptedPassword;

    /**
     *
     * This variable is used to store a copy of the salt-encrypted password in Client,
     * at the time the unencrypted password was created and stored in a ClientMigration entity.
     * The value of this field can then be compared to the password value in Client,
     * to check if the password has changed or not.
     *
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @JMS\Type("string")
     */
    private $encryptedPassword;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\Date
     * @JMS\Type("DateTime")
     */
    private $passwordCreationDate;

    /**
     * @var Client
     *
     * @ORM\OneToOne(targetEntity="Client")
     * @JoinColumn(name="client_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Client")
     */
    private $client;

    /**
     * @return string
     */
    public function getUnencryptedPassword()
    {
        return $this->unencryptedPassword;
    }

    /**
     * @param string $unencryptedPassword
     */
    public function setUnencryptedPassword($unencryptedPassword)
    {
        $this->unencryptedPassword = $unencryptedPassword;
    }

    /**
     * @return string
     */
    public function getEncryptedPassword()
    {
        return $this->encryptedPassword;
    }

    /**
     * @param string $encryptedPassword
     */
    public function setEncryptedPassword($encryptedPassword)
    {
        $this->encryptedPassword = $encryptedPassword;
        $this->passwordCreationDate = new \DateTime('now');
    }

    /**
     * @return \DateTime
     */
    public function getPasswordCreationDate()
    {
        return $this->passwordCreationDate;
    }

    /**
     * @return \AppBundle\Entity\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param \AppBundle\Entity\Client $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }


}