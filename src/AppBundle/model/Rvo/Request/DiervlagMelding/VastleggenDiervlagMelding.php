<?php


namespace AppBundle\model\Rvo\Request\DiervlagMelding;


use AppBundle\Entity\DeclareAnimalFlag;

class VastleggenDiervlagMelding
{
    /** @var VastleggenDiervlagMeldingRequest */
    public $vastleggenDiervlagMeldingRequest;

    public function __construct(DeclareAnimalFlag $flag)
    {
        $this->vastleggenDiervlagMeldingRequest = new VastleggenDiervlagMeldingRequest($flag);
    }
}