<?php


namespace AppBundle\Util;


use Curl\Curl;
use Symfony\Component\HttpFoundation\Response;

class CurlUtil
{
    public static function is200Response(Curl $curl): bool
    {
        return $curl->getHttpStatus() === Response::HTTP_OK;
    }
}