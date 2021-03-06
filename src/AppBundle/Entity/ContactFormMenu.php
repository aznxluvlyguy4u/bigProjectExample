<?php

namespace AppBundle\Entity;

use AppBundle\Traits\EntityClassInfo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ReasonOfLossMenu
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ContactFormMenuRepository")
 * @package AppBundle\Entity
 */
class ContactFormMenu extends DropDownMenu
{
    use EntityClassInfo;

    public function __construct()
    {
        parent::__construct();
    }
}