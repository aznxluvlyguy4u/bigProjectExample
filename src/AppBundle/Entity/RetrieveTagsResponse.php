<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class RetrieveTagsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveTagsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveTagsResponse extends DeclareBaseResponse {

  /**
   * @var RetrieveTags
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="RetrieveTags", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\RetrieveTags")
   */
  private $retrieveTagsRequestMessage;


    /**
     * Set retrieveTagsRequestMessage
     *
     * @param \AppBundle\Entity\RetrieveTags $retrieveTagsRequestMessage
     *
     * @return RetrieveTagsResponse
     */
    public function setRetrieveTagsRequestMessage(\AppBundle\Entity\RetrieveTags $retrieveTagsRequestMessage = null)
    {
        $this->retrieveTagsRequestMessage = $retrieveTagsRequestMessage;

        return $this;
    }

    /**
     * Get retrieveTagsRequestMessage
     *
     * @return \AppBundle\Entity\RetrieveTags
     */
    public function getRetrieveTagsRequestMessage()
    {
        return $this->retrieveTagsRequestMessage;
    }
}
