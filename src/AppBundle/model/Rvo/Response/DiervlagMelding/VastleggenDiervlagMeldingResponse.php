<?php


namespace AppBundle\model\Rvo\Response\DiervlagMelding;


use AppBundle\model\Rvo\Response\RvoMeldingResponseBase;
use JMS\Serializer\Annotation as JMS;

class VastleggenDiervlagMeldingResponse extends RvoMeldingResponseBase
{
    /**
     * @var DiergegevensDiervlagMeldingResponse
     * @JMS\Type("AppBundle\model\Rvo\Response\DiervlagMelding\DiergegevensDiervlagMeldingResponse")
     * @JMS\SerializedName("diergegevensDiervlagMeldingResponse")
     */
    public $diergegevensDiervlagMeldingResponse;

}