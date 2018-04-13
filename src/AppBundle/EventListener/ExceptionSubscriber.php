<?php


namespace AppBundle\EventListener;


use AppBundle\Util\ResultUtil;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException',
        );
    }


    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        /**
         * This function catches exceptions and immediately let the controller return the response,
         * UNLESS the exception was caught in a try-catch block.
         *
         * TODO only activate the code below AFTER testing-&-refactoring the exception-response flow in all location in the code!
         */
        return;

        $exception = $event->getException();
        $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_INTERNAL_SERVER_ERROR);

        if ($exception instanceof AccessDeniedHttpException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_FORBIDDEN);
        }

        if ($exception instanceof NotFoundHttpException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_NOT_FOUND);
        }

        if ($exception instanceof InsufficientAuthenticationException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_UNAUTHORIZED);
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_TOO_MANY_REQUESTS);
        }

        if ($exception instanceof BadRequestHttpException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_FORBIDDEN);
        }

        if ($exception instanceof PreconditionFailedHttpException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_PRECONDITION_REQUIRED);
        }

        if ($exception instanceof BadRequestHttpException) {
            $defaultErrorMessage = ResultUtil::getDefaultErrorMessage(Response::HTTP_BAD_REQUEST);
        }

        $this->logException($exception);
        $this->setErrorResponse($event, $exception, $defaultErrorMessage);

    }


    /**
     * @param GetResponseForExceptionEvent $event
     * @param \Exception $exception
     * @param int $defaultErrorMessage
     */
    private function setErrorResponse(GetResponseForExceptionEvent $event,
                                      \Exception $exception,
                                      $defaultErrorMessage = Response::HTTP_INTERNAL_SERVER_ERROR
    )
    {
        $errorMessage = empty($exception->getMessage()) ? $defaultErrorMessage : $exception->getMessage();
        $response = ResultUtil::standardErrorResult(Response::HTTP_BAD_REQUEST, $errorMessage);
        $event->setResponse($response);
    }


    /**
     * @param \Exception $exception
     */
    private function logException(\Exception $exception)
    {
        $this->logger->error($exception->getTraceAsString());
        $this->logger->error($exception->getMessage());
    }

}