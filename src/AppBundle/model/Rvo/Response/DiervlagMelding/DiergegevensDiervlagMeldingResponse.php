<?php


namespace AppBundle\model\Rvo\Response\DiervlagMelding;


use AppBundle\model\Rvo\Response\RvoMeldingResponseVerwerkingsResultaat;
use JMS\Serializer\Annotation as JMS;

class DiergegevensDiervlagMeldingResponse
{
    /**
     * @var RvoMeldingResponseVerwerkingsResultaat
     * @JMS\Type("AppBundle\model\Rvo\Response\RvoMeldingResponseVerwerkingsResultaat")
     * @JMS\SerializedName("verwerkingsresultaat")
     */
    public $verwerkingsresultaat;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("selDierLandcode")
     */
    public $selDierLandcode;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("selDierLevensnummer")
     */
    public $selDierLevensnummer;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("selDierWerknummer")
     */
    public $selDierWerknummer;

    /**
     * @var int
     * @JMS\Type("integer")
     * @JMS\SerializedName("dierSoort")
     */
    public $dierSoort;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("vlagsoortCodeReden")
     */
    public $vlagsoortCodeReden;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime<'d-m-Y'>")
     * @JMS\SerializedName("datumIngang")
     */
    public $datumIngang;

    /**
     * @var string|null
     * @JMS\Type("DateTime<'d-m-Y'>")
     * @JMS\SerializedName("datumEinde")
     */
    public $datumEinde;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("meldingnummer")
     */
    public $meldingnummer;
}