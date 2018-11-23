<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class DeclareAnimalFlag
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareAnimalFlagResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareAnimalFlagResponse extends DeclareBaseResponse
{
    use EntityClassInfo;

    /**
     * @var \AppBundle\Entity\DeclareAnimalFlag
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="DeclareAnimalFlag", cascade={"persist"}, inversedBy="responses")
     * @JMS\Type("AppBundle\Entity\DeclareAnimalFlag")
     */
    private $declareAnimalFlagRequestMessage;

    /**
     * Set declareAnimalFlagRequestMessage
     *
     * @param \AppBundle\Entity\DeclareAnimalFlag $declareAnimalFlagRequestMessage
     *
     * @return DeclareAnimalFlagResponse
     */
    public function setDeclareAnimalFlagRequestMessage(\AppBundle\Entity\DeclareAnimalFlag $declareAnimalFlagRequestMessage = null)
    {
        $this->declareAnimalFlagRequestMessage = $declareAnimalFlagRequestMessage;

        return $this;
    }

    /**
     * Get declareAnimalFlagRequestMessage
     *
     * @return \AppBundle\Entity\DeclareAnimalFlag
     */
    public function getDeclareAnimalFlagRequestMessage()
    {
        return $this->declareAnimalFlagRequestMessage;
    }
}
