<?php


namespace AppBundle\EventListener;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\Environment;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\ResultUtil;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ExceptionListener
{
    /** @var string */
    private $environment;

    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger, $environment)
    {
        $this->logger = $logger;
        $this->environment = $environment;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getException();

        if (self::exceptionShouldBeLogged($exception)) {
            $this->logger->error($exception->getMessage());
            $this->logger->error($exception->getTraceAsString());
        }

        $code = self::getHttpResponseCodeFromException($exception);
        $errorMessage = empty($exception->getMessage()) || self::isSensitiveException($exception)
            ? self::getDefaultErrorMessage($code) : $exception->getMessage();
        $errorData = $this->environment !== Environment::PROD ? self::nestErrorTrace($exception) : null;
        $errorResponse = $this->errorResult($errorMessage, $code, $errorData);
        $event->setResponse($errorResponse);
    }


    /**
     * @param \Exception $exception
     * @return array
     */
    private static function nestErrorTrace($exception): array
    {
        $nestedErrorTrace = [];
        $nestedErrorTrace[] = $exception->getMessage();

        foreach (explode('#',$exception->getTraceAsString()) as $line) {
            $nestedErrorTrace[] = explode(': ', $line);
        }

        return $nestedErrorTrace;
    }


    /**
     * @param string $mainErrorMessage
     * @param int $code
     * @param string|mixed $mainErrorData
     * @param array $errorDetailsList
     * @return JsonResponse
     */
    public function errorResult($mainErrorMessage, $code, $mainErrorData = null, array $errorDetailsList = []): JsonResponse
    {
        if (!empty($errorDetailsList)) {
            $mainErrorData = $errorDetailsList;
        }
        return ResultUtil::errorResult($mainErrorMessage, $code, $mainErrorData, true);
    }


    /**
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public function defaultErrorResult($message, $code): JsonResponse
    {
        return $this->errorResult(
            (!$message ? self::getDefaultErrorMessage($code) : $message),
            $code
        );
    }


    /**
     * @param $errorCode
     * @return mixed|null
     */
    private static function getDefaultErrorMessage($errorCode)
    {
        return ArrayUtil::get($errorCode, Response::$statusTexts, Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR]);
    }


    /**
     * @param \Exception $exception
     * @return int
     */
    private static function getHttpResponseCodeFromException(\Exception $exception)
    {
        switch (true) {
            case $exception instanceof AccessDeniedHttpException: return Response::HTTP_FORBIDDEN;
            case $exception instanceof NotFoundHttpException: return Response::HTTP_NOT_FOUND;
            case $exception instanceof InsufficientAuthenticationException: return Response::HTTP_UNAUTHORIZED;
            case $exception instanceof TooManyRequestsHttpException: return Response::HTTP_TOO_MANY_REQUESTS;
            case $exception instanceof UnauthorizedHttpException: return Response::HTTP_UNAUTHORIZED;
            case $exception instanceof PreconditionFailedHttpException: return Response::HTTP_PRECONDITION_FAILED;
            case $exception instanceof PreconditionRequiredHttpException: return Response::HTTP_PRECONDITION_REQUIRED;
            case $exception instanceof BadRequestHttpException: return Response::HTTP_BAD_REQUEST;
            default: return empty($exception->getCode()) || $exception->getCode() === Response::HTTP_OK
                ? Response::HTTP_INTERNAL_SERVER_ERROR : $exception->getCode();
        }
    }


    /**
     * Ignore logging for general user input validations.
     *
     * @param \Exception $exception
     * @return bool
     */
    private static function exceptionShouldBeLogged(\Exception $exception): bool
    {
        return !(
            $exception instanceof PreconditionFailedHttpException ||
            $exception instanceof PreconditionRequiredHttpException
        );
    }


    /**
     * @param $exception
     * @return bool
     */
    private static function isSensitiveException($exception): bool
    {
        return !$exception
                || $exception instanceof \Doctrine\DBAL\DBALException // Prevent leaking of database schema
                || $exception instanceof ORMInvalidArgumentException
                || $exception instanceof ORMException
            ;
    }
}