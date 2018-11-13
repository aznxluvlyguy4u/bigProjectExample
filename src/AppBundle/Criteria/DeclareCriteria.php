<?php


namespace AppBundle\Criteria;

use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestStateType;
use Doctrine\Common\Collections\Criteria;

class DeclareCriteria
{
    /**
     * @param string $requestState
     * @return Criteria
     */
    public static function byRequestState(string $requestState)
    {
        return Criteria::create()->where(
            Criteria::expr()->eq(Constant::REQUEST_STATE_NAMESPACE, $requestState)
        );
    }


    /**
     * @return Criteria
     */
    public static function byOpenRequestState()
    {
        return self::byRequestState(RequestStateType::OPEN);
    }


    /**
     * @return Criteria
     */
    public static function byFinishedOrFinishedWithWarningRequestState()
    {
        return Criteria::create()->where(
            Criteria::expr()->orX(
                Criteria::expr()->eq(Constant::REQUEST_STATE_NAMESPACE, RequestStateType::FINISHED),
                Criteria::expr()->eq(Constant::REQUEST_STATE_NAMESPACE, RequestStateType::FINISHED_WITH_WARNING)
            )
        );
    }
}