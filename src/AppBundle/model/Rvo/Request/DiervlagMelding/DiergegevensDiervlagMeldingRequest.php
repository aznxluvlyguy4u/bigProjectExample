<?php


namespace AppBundle\model\Rvo\Request\DiervlagMelding;


use AppBundle\Entity\DeclareAnimalFlag;
use AppBundle\model\Rvo\Request\RvoMeldingRequestBase;

class DiergegevensDiervlagMeldingRequest
{
    /** @var string */
    public $selDierLandcode;
    /** @var string */
    public $selDierLevensnummer;
    /** @var string */
    public $selDierWerknummer;
    /** @var int */
    public $dierSoort;
    /** @var string */
    public $vlagsoortCodeReden;

    /** @var string */
    public $datumIngang;
    /** @var string|null */
    public $datumEinde;

    public function __construct(DeclareAnimalFlag $flag)
    {
        $this->selDierLandcode = $flag->getAnimal()->getUlnCountryCode();
        $this->selDierLevensnummer = $flag->getAnimal()->getUlnNumber();
        $this->selDierWerknummer = $flag->getAnimal()->getAnimalOrderNumber();
        $this->dierSoort = $flag->getAnimal()->getAnimalType();
        $this->vlagsoortCodeReden = $flag->getFlagType();
        $this->datumIngang = $flag->getFlagStartDate()->format(RvoMeldingRequestBase::RVO_DATE_FORMAT);
        $this->datumEinde = $flag->getFlagEndDate() ? $flag->getFlagEndDate()->format(RvoMeldingRequestBase::RVO_DATE_FORMAT) : null;
    }
}