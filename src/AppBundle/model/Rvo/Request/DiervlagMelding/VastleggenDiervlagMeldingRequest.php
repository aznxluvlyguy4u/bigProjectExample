<?php


namespace AppBundle\model\Rvo\Request\DiervlagMelding;


use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\model\Rvo\Request\RvoMeldingRequestBase;

class VastleggenDiervlagMeldingRequest extends RvoMeldingRequestBase
{
    /** @var DiergegevensDiervlagMeldingRequest */
    public $diergegevensDiervlagMeldingRequest;

    public function __construct(DeclareAnimalFlag $flag)
    {
        parent::__construct($flag);

        $this->diergegevensDiervlagMeldingRequest  = new DiergegevensDiervlagMeldingRequest($flag);
    }
}