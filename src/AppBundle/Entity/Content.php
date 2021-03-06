<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Content
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ContentRepository")
 * @package AppBundle\Entity
 */
class Content
{
    use EntityClassInfo;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $dashBoardIntroductionText;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     * @JMS\Type("string")
     */
    private $nsfoContactInformation;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDashBoardIntroductionText()
    {
        return $this->dashBoardIntroductionText;
    }

    /**
     * @param string $dashBoardIntroductionText
     */
    public function setDashBoardIntroductionText($dashBoardIntroductionText)
    {
        $this->dashBoardIntroductionText = $dashBoardIntroductionText;
    }

    /**
     * @return string
     */
    public function getNsfoContactInformation()
    {
        return $this->nsfoContactInformation;
    }

    /**
     * @param string $nsfoContactInformation
     */
    public function setNsfoContactInformation($nsfoContactInformation)
    {
        $this->nsfoContactInformation = $nsfoContactInformation;
    }




}