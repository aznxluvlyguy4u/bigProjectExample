<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ReasonOfLossMenu
 * @ORM\Entity(repositoryClass="AppBundle\Entity\DropDownMenuRepository")
 * @package AppBundle\Entity
 */
class ReasonOfLossMenu extends DropDownMenu
{
    public function __construct()
    {
        parent::__construct();
    }
}