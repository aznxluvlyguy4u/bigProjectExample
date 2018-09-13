<?php


namespace AppBundle\Service\ExternalProvider;


class ExternalProviderBase
{
    /** @var ExternalProviderAuthenticator */
    private $authenticator;


    public function __construct(ExternalProviderAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     * @return ExternalProviderAuthenticator
     */
    public function getAuthenticator(): ExternalProviderAuthenticator
    {
        return $this->authenticator;
    }

}