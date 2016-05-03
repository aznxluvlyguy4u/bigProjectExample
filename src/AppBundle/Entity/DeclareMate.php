<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclareMate
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclareMateRepository")
 * @package AppBundle\Entity
 */
class DeclareMate extends DeclareBase
{
//TODO


    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclareMate
     */
    public function setUbn($ubn)
    {
        $this->ubn = $ubn;

        return $this;
    }

    /**
     * Get ubn
     *
     * @return string
     */
    public function getUbn()
    {
        return $this->ubn;
    }
}
