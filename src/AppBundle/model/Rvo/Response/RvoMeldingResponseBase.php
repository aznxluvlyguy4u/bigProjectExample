<?php


namespace AppBundle\model\Rvo\Response;

use JMS\Serializer\Annotation as JMS;

class RvoMeldingResponseBase
{
    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("requestID")
     */
    public $requestID;

    /**
     * relationNumberKeeper
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("relatienummerHouder")
     */
    public $relatienummerHouder;

    /**
     * UBN
     *
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("meldingeenheid")
     */
    public $meldingeenheid;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("actie")
     */
    public $actie;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\SerializedName("herstelIndicator")
     */
    public $herstelIndicator;
}