<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class StarRanking
 * @ORM\Entity(repositoryClass="AppBundle\Entity\StarRankingRepository")
 * @package AppBundle\Entity
 */
class StarRanking {
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
    *
    * @ORM\Column(type="integer")
    * @Assert\Length(max = 3)
    * @Assert\NotBlank
    * @JMS\Type("integer")
    */
    private $lowerLimitPercentage;

    /**
    * @var integer
    *
    * @ORM\Column(type="integer")
    * @Assert\Length(max = 3)
    * @Assert\NotBlank
    * @JMS\Type("integer")
    */
    private $upperLimitPercentage;

    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"default":0})
     * @Assert\Length(max = 2)
     * @Assert\NotBlank
     * @JMS\Type("float")
     */
    private $stars;


    /**
     * StarRanking constructor.
     * @param int $lowerLimitPercentage
     * @param int $upperLimitPercentage
     * @param int $stars
     */
    public function __construct($lowerLimitPercentage = 0, $upperLimitPercentage = 0, $stars = 0)
    {
        $this->setLowerLimitPercentage($lowerLimitPercentage);
        $this->setUpperLimitPercentage($upperLimitPercentage);
        $this->setStars($stars);
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
     * @return int
     */
    public function getLowerLimitPercentage()
    {
        return $this->lowerLimitPercentage;
    }

    /**
     * @param int $lowerLimitPercentage
     */
    public function setLowerLimitPercentage($lowerLimitPercentage)
    {
        $this->lowerLimitPercentage = $lowerLimitPercentage;
    }

    /**
     * @return int
     */
    public function getUpperLimitPercentage()
    {
        return $this->upperLimitPercentage;
    }

    /**
     * @param int $upperLimitPercentage
     */
    public function setUpperLimitPercentage($upperLimitPercentage)
    {
        $this->upperLimitPercentage = $upperLimitPercentage;
    }

    /**
     * @return float
     */
    public function getStars()
    {
        return $this->stars;
    }

    /**
     * @param float $stars
     */
    public function setStars($stars)
    {
        $this->stars = $stars;
    }


}
