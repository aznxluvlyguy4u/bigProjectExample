<?php


namespace AppBundle\Service\Rvo;


use AppBundle\Enumerator\RvoPathEnum;

class RvoMeldingenServiceBase extends RvoServiceBase
{
    const SOAP_ENVELOPE_DETAILS = [
        'xmlns:mel="http://www.ienr.org/schemas/services/meldingenWS_v2_0"',
        'xmlns:mel1="http://www.ienr.org/schemas/types/meldingen_v2_0"',
    ];

    /**
     * @required
     */
    public function init()
    {
        $this->path = RvoPathEnum::MELDINGEN;
    }
}