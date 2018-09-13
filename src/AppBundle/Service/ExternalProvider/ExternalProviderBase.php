<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Constant\ExternalProviderSetting;

class ExternalProviderBase
{
    /** @var ExternalProviderAuthenticator */
    private $authenticator;

    /** @var int */
    private $retryCount;

    public function __construct(ExternalProviderAuthenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->resetRetryCount();
    }

    /**
     * @return ExternalProviderAuthenticator
     */
    public function getAuthenticator(): ExternalProviderAuthenticator
    {
        return $this->authenticator;
    }


    /**
     * @param \Exception $exception
     * @return bool
     */
    protected function allowRetryTwinfieldApiCall(\Exception $exception): bool
    {
        return $this->isReLogonException($exception) && !$this->hasMaxRetryCountBeenExceeded();
    }


    /**
     * @param \Exception $exception
     * @return bool
     */
    protected function isReLogonException(\Exception $exception): bool
    {
        return $exception->getMessage() === ExternalProviderSetting::RE_LOGON_ERROR_MESSAGE;
    }


    protected function resetRetryCount(): void
    {
        $this->retryCount = 0;
    }


    /**
     * @return bool
     */
    protected function hasMaxRetryCountBeenExceeded(): bool
    {
        return $this->retryCount > ExternalProviderSetting::MAX_RE_AUTHENTICATION_TRIES;
    }


    /**
     * @return int the incremented result
     */
    protected function incrementRetryCount(): int
    {
        return ++$this->retryCount;
    }
}