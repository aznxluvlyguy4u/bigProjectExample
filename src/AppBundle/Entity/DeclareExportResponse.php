<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareExport;

/**
 * Class DeclareExportResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareExportResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareExportResponse extends DeclareBaseResponse {

  /**
   * @var DeclareExport
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareExport", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareExport")
   */
  private $declareExportRequestMessage;

    /**
     * Set declareExportRequestMessage
     *
     * @param \AppBundle\Entity\DeclareExport $declareExportRequestMessage
     *
     * @return DeclareExportResponse
     */
    public function setDeclareExportRequestMessage(\AppBundle\Entity\DeclareExport $declareExportRequestMessage = null)
    {
        $this->declareExportRequestMessage = $declareExportRequestMessage;

        return $this;
    }

    /**
     * Get declareExportRequestMessage
     *
     * @return \AppBundle\Entity\DeclareExport
     */
    public function getDeclareExportRequestMessage()
    {
        return $this->declareExportRequestMessage;
    }
}
