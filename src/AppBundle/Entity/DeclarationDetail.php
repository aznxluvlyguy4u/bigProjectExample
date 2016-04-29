<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use \AppBundle\Entity\Animal;
use \DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class DeclarationDetail
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DeclarationDetailRepository")
 * @package AppBundle\Entity
 */
class DeclarationDetail extends DeclareBase
{
//TODO


    /**
     * Set ubn
     *
     * @param string $ubn
     *
     * @return DeclarationDetail
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
