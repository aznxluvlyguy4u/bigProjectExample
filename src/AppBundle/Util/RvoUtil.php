<?php


namespace AppBundle\Util;


use AppBundle\Enumerator\HttpMethod;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\RvoPathEnum;

class RvoUtil
{
    const RVO_PATH = 'RVO_PATH';
    const HTTP_METHOD = 'HTTP_METHOD';

    const REQUEST_TYPES = [
            RequestType::DECLARE_ANIMAL_FLAG => [
                self::RVO_PATH => RvoPathEnum::MELDINGEN,
                self::HTTP_METHOD => HttpMethod::POST,
            ]
    ];

    public static function getRvoUrl(string $requestType, string $rvoIrBaseUrl): string
    {
        $rvoPath = RvoUtil::REQUEST_TYPES[$requestType][RvoUtil::RVO_PATH];
        return $rvoIrBaseUrl . $rvoPath;
    }

    public static function getHttpMethod(string $requestType): string
    {
        return RvoUtil::REQUEST_TYPES[$requestType][RvoUtil::HTTP_METHOD];
    }

}