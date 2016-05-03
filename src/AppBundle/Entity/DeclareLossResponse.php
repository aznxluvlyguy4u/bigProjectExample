<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\DeclareLoss;

/**
 * Class DeclareLossResponse
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareLossResponseRepository")
 * @package AppBundle\Entity
 */
class DeclareLossResponse extends DeclareBaseResponse {

  /**
   * @var DeclareLoss
   *
   * @Assert\NotBlank
   * @ORM\ManyToOne(targetEntity="DeclareLoss", cascade={"persist"}, inversedBy="responses")
   * @JMS\Type("AppBundle\Entity\DeclareLoss")
   */
  private $declareLossRequestMessage;
//JColumn(name="declare_loss_request_message_id", referencedColumnName="id")

    /**
     * Set declareLossRequestMessage
     *
     * @param \AppBundle\Entity\DeclareLoss $declareLossRequestMessage
     *
     * @return DeclareLossResponse
     */
    public function setDeclareLossRequestMessage(\AppBundle\Entity\DeclareLoss $declareLossRequestMessage = null)
    {
        $this->declareLossRequestMessage = $declareLossRequestMessage;

        return $this;
    }

    /**
     * Get declareLossRequestMessage
     *
     * @return \AppBundle\Entity\DeclareLoss
     */
    public function getDeclareLossRequestMessage()
    {
        return $this->declareLossRequestMessage;
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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return DeclareLossResponse
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
