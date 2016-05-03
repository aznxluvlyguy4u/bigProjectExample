<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class DeclareImportResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareImportResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareImportResponse extends DeclareBaseResponse {

  /**
   * @var DeclareImport
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareImport", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareImport")
   */
  private $declareImportRequestMessage;


    /**
     * Set declareImportRequestMessage
     *
     * @param \AppBundle\Entity\DeclareImport $declareImportRequestMessage
     *
     * @return DeclareImportResponse
     */
    public function setDeclareImportRequestMessage(\AppBundle\Entity\DeclareImport $declareImportRequestMessage = null)
    {
        $this->declareImportRequestMessage = $declareImportRequestMessage;

        return $this;
    }

    /**
     * Get declareImportRequestMessage
     *
     * @return \AppBundle\Entity\DeclareImport
     */
    public function getDeclareImportRequestMessage()
    {
        return $this->declareImportRequestMessage;
    }
}
