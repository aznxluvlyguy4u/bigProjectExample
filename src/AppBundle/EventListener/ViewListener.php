<?php


namespace AppBundle\EventListener;

use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ViewListener
{
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if ($result === true) {
            $result = 'OK';
        }

        $response = ResultUtil::successResult($result);

        $event->setResponse($response);
    }
}