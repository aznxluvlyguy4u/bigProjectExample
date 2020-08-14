<?php


namespace AppBundle\Service\Rvo\SoapMessageBuilder;


class RvoMeldingenSoapMessageBuilderBase extends RvoSoapMessageBuilderBase
{
    const SOAP_ENVELOPE_DETAILS = [
        'xmlns:mel="http://www.ienr.org/schemas/services/meldingenWS_v2_0"',
        'xmlns:mel1="http://www.ienr.org/schemas/types/meldingen_v2_0"',
    ];

}