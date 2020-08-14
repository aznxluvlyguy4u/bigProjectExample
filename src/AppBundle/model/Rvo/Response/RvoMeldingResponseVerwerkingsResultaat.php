<?php


namespace AppBundle\model\Rvo\Response;

use JMS\Serializer\Annotation as JMS;

class RvoMeldingResponseVerwerkingsResultaat
{
    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("soortFoutIndicator")
     */
    public $soortFoutIndicator;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("succesIndicator")
     */
    public $succesIndicator;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("foutcode")
     */
    public $foutcode;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("foutmelding")
     */
    public $foutmelding;
}