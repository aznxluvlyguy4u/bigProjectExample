<?php

namespace AppBundle\Entity;


use AppBundle\Entity\Client;
use AppBundle\Enumerator\MigrationStatus;
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
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

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
     * @var string
     */
    private $migrationStatus;

    /**
     * @var Client
     *
     * @ORM\OneToOne(targetEntity="Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     * @JMS\Type("AppBundle\Entity\Client")
     */
    private $client;


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
        $this->setMigrationStatus(MigrationStatus::MIGRATED);
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



    /**
     * Set passwordCreationDate
     *
     * @param \DateTime $passwordCreationDate
     *
     * @return ClientMigration
     */
    public function setPasswordCreationDate($passwordCreationDate)
    {
        $this->passwordCreationDate = $passwordCreationDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationStatus()
    {
        return $this->migrationStatus;
    }

    /**
     * @param string $migrationStatus
     */
    public function setMigrationStatus($migrationStatus)
    {
        $this->migrationStatus = $migrationStatus;
    }



}
