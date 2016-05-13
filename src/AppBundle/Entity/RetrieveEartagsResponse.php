<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class RetrieveEartagsResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\RetrieveEartagsResponseRepository")
 * @package AppBundle\Entity
 */
class RetrieveEartagsResponse extends DeclareBaseResponse {

  /**
   * @var RetrieveEartags
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="RetrieveEartags", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\RetrieveEartags")
   */
  private $retrieveEartagsRequestMessage;


    /**
     * Set retrieveEartagsRequestMessage
     *
     * @param \AppBundle\Entity\RetrieveEartags $retrieveEartagsRequestMessage
     *
     * @return RetrieveEartagsResponse
     */
    public function setRetrieveEartagsRequestMessage(\AppBundle\Entity\RetrieveEartags $retrieveEartagsRequestMessage = null)
    {
        $this->retrieveEartagsRequestMessage = $retrieveEartagsRequestMessage;

        return $this;
    }

    /**
     * Get retrieveEartagsRequestMessage
     *
     * @return \AppBundle\Entity\RetrieveEartags
     */
    public function getRetrieveEartagsRequestMessage()
    {
        return $this->retrieveEartagsRequestMessage;
    }
}
