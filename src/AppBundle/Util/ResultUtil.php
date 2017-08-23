<?php


namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;

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


    /**
     * @param string $message
     * @param int $code The HTTP code
     * @param array $errors
     * @return JsonResponse
     */
    public static function errorResult($message, $code, $errors = array())
    {
        //Success message
        if($errors == null || sizeof($errors) == 0){
            $result = array(
                Constant::MESSAGE_NAMESPACE => $message,
                Constant::CODE_NAMESPACE => $code);

            //Error message
        } else {
            $result = array();
            foreach ($errors as $errorMessage) {
                $errorArray = [
                    Constant::CODE_NAMESPACE => $code,
                    Constant::MESSAGE_NAMESPACE => $errorMessage
                ];
                $result[] = $errorArray;
            }
        }

        return new JsonResponse([JsonInputConstant::RESULT => $result], $code);
    }
}