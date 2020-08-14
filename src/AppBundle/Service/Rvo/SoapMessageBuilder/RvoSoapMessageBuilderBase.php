<?php


namespace AppBundle\Service\Rvo\SoapMessageBuilder;


use Psr\Log\LoggerInterface;

abstract class RvoSoapMessageBuilderBase
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @required
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}