<?php


namespace AppBundle\model\Rvo\Request;


use AppBundle\Entity\DeclareBaseInterface;

class RvoMeldingRequestBase
{
    const RVO_DATE_FORMAT = 'd-m-Y';

    /** @var string */
    public $requestID;
    /** @var string */
    public $relatienummerHouder;
    /** @var string */
    public $meldingeenheid;
    /** @var string */
    public $actie;
    /** @var string */
    public $herstelIndicator;

    public function __construct(DeclareBaseInterface $declareBase)
    {
        $this->requestID = $declareBase->getRequestId();
        $this->relatienummerHouder = $declareBase->getRelationNumberKeeper();
        $this->meldingeenheid = $declareBase->getUbn();
        $this->actie = $declareBase->getAction();
        $this->herstelIndicator = $declareBase->getRecoveryIndicator();
    }
}