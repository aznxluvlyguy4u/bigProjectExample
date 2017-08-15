<?php


namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;

class ResultUtil
{
    const SUCCESS_CODE = 200;

    /**
     * @param $result
     * @return JsonResponse
     */
    public static function successResult($result)
    {
        return new JsonResponse([Constant::RESULT_NAMESPACE => $result], self::SUCCESS_CODE);
    }
}