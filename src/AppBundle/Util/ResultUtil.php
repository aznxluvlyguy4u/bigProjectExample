<?php


namespace AppBundle\Util;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Constant;
use AppBundle\Constant\JsonInputConstant;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResultUtil
{

    /**
     * @param $result
     * @return JsonResponse
     */
    public static function successResult($result)
    {
        if ($result === null) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return new JsonResponse([Constant::RESULT_NAMESPACE => $result], Response::HTTP_OK);
    }


    /**
     * @param $result
     * @param string $message
     * @param $code
     * @return JsonResponse
     */
    public static function multiEditErrorResult($result, $message, $code)
    {
        return new JsonResponse([
            Constant::RESULT_NAMESPACE => $result,
            Constant::MESSAGE_NAMESPACE => $message,
        ], $code);
    }


    /**
     * @param int $errorCode
     * @param string $errorMessage
     * @return JsonResponse
     */
    public static function standardErrorResult($errorCode, $errorMessage = null)
    {
        return ResultUtil::errorResult($errorMessage ? $errorMessage : Response::$statusTexts[$errorCode], $errorCode);
    }


    /**
     * @param $errorCode
     * @return mixed|null
     */
    public static function getDefaultErrorMessage($errorCode)
    {
        return ArrayUtil::get($errorCode, Response::$statusTexts, Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR]);
    }


    /**
     * @return JsonResponse
     */
    public static function internalServerError()
    {
        return ResultUtil::errorResult('INTERNAL SERVER ERROR', Response::HTTP_INTERNAL_SERVER_ERROR);
    }


    /**
     * @return JsonResponse
     */
    public static function badRequest()
    {
        return ResultUtil::errorResult('BAD REQUEST', Response::HTTP_BAD_REQUEST);
    }


    /**
     * @return JsonResponse
     */
    public static function notFound()
    {
        return ResultUtil::errorResult('NOT FOUND', Response::HTTP_NOT_FOUND);
    }


    /**
     * @return JsonResponse
     */
    public static function noContent()
    {
        return ResultUtil::errorResult('NO CONTENT', Response::HTTP_NO_CONTENT);
    }


    /**
     * @param Exception $exception
     * @return JsonResponse
     */
    public static function errorResultByException(Exception $exception)
    {
        $errorCode = $exception->getCode() === 0 ? Response::HTTP_INTERNAL_SERVER_ERROR : $exception->getCode();
        if ($errorCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
            // hide exception details, for security reasons
            return self::internalServerError();
        }
        return ResultUtil::errorResult($exception->getMessage(), $errorCode);
    }


    /**
     * @param string $message
     * @param int $code The HTTP code
     * @param array|string|int $errorsOrErrorData
     * @param boolean $extraInputIsData
     * @return JsonResponse
     */
    public static function errorResult($message, $code, $errorsOrErrorData = array(), $extraInputIsData = false)
    {
        //Success message
        if(empty($errorsOrErrorData)) {
            $result = array(
                Constant::MESSAGE_NAMESPACE => $message,
                Constant::CODE_NAMESPACE => $code);

            //Error message

        } elseif (!is_array($errorsOrErrorData) || $extraInputIsData) {

            return new JsonResponse([
                JsonInputConstant::RESULT => [
                    Constant::CODE_NAMESPACE => $code,
                    Constant::MESSAGE_NAMESPACE => $message,
                    Constant::DATA => $errorsOrErrorData,
                ]
            ], $code);

        } else {
            $result = array();
            foreach ($errorsOrErrorData as $errorMessage) {
                $errorArray = [
                    Constant::CODE_NAMESPACE => $code,
                    Constant::MESSAGE_NAMESPACE => $errorMessage
                ];
                $result[] = $errorArray;
            }
        }

        return new JsonResponse([JsonInputConstant::RESULT => $result], $code);
    }


    /**
     * @param array $arrayData
     * @return string
     * @throws Exception
     */
    public static function getMessageStringFromErrorResult(array $arrayData): string
    {
        if (key_exists(JsonInputConstant::RESULT, $arrayData)) {
            $results = $arrayData[JsonInputConstant::RESULT];
            if (key_exists(Constant::MESSAGE_NAMESPACE, $results)) {
                $message = $results[Constant::MESSAGE_NAMESPACE];
                if (key_exists(Constant::DATA, $results)) {
                    $message .= $results[Constant::DATA];
                }
                return $message;
            }

            $message = '';
            $prefix = '';
            foreach ($results as $result) {
                if (key_exists(Constant::MESSAGE_NAMESPACE, $results)) {
                    $message .= $prefix . $results[Constant::MESSAGE_NAMESPACE];
                    $prefix = '  ';
                }
            }
            return $message;
        } else {
           return self::errorResult('No result found.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @return JsonResponse
     */
    public static function unauthorized()
    {
        return self::errorResult('UNAUTHORIZED', Response::HTTP_UNAUTHORIZED);
    }


    /**
     * @param JsonResponse|SymfonyJsonResponse $response
     * @return mixed|null
     */
    public static function getResultArray($response)
    {
        $data = json_decode($response->getContent(), true);
        if (is_array($data)) {
            return ArrayUtil::get(Constant::RESULT_NAMESPACE, $data);
        }
        return null;
    }


    /**
     * @param string $key
     * @param JsonResponse|SymfonyJsonResponse $response
     * @return mixed|null
     */
    public static function getFromResult($key, $response)
    {
        $result = self::getResultArray($response);
        if (is_array($result)) {
            return ArrayUtil::get($key, $result);
        }
        return null;
    }

}
