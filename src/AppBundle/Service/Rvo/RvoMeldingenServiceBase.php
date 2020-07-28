<?php


namespace AppBundle\Service\Rvo;


use AppBundle\Enumerator\RvoPathEnum;

class RvoMeldingenServiceBase extends RvoServiceBase
{
    /**
     * @required
     */
    public function init()
    {
        $this->path = RvoPathEnum::MELDINGEN;
    }
}