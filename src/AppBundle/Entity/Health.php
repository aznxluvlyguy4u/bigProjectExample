<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Class Health
 *
 * @ORM\Table(name="health")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\HealthRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"Animal" = "Animal", "LocationHealth" = "LocationHealth", "AnimalHealth" = "AnimalHealth"})
 * @package AppBundle\Entity\Health
 * @ExclusionPolicy("all")
 */
abstract class Health
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * maedi_visna is 'zwoegerziekte' in Dutch
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    protected $maediVisnaStatus;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @JMS\Type("string")
     * @Expose
     */
    protected $scrapieStatus;

    /**
     * @return string
     */
    public function getMaediVisnaStatus()
    {
        return $this->maediVisnaStatus;
    }

    /**
     * @param string $maediVisnaStatus
     */
    public function setMaediVisnaStatus($maediVisnaStatus)
    {
        $this->maediVisnaStatus = $maediVisnaStatus;
    }

    /**
     * @return string
     */
    public function getScrapieStatus()
    {
        return $this->scrapieStatus;
    }

    /**
     * @param string $scrapieStatus
     */
    public function setScrapieStatus($scrapieStatus)
    {
        $this->scrapieStatus = $scrapieStatus;
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
}
