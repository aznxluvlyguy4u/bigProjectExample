<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class ReasonOfDepartMenu
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ReasonOfDepartMenuRepository")
 * @package AppBundle\Entity
 */
class ReasonOfDepartMenu extends DropDownMenu
{
    use EntityClassInfo;

    public function __construct()
    {
        parent::__construct();
    }
}