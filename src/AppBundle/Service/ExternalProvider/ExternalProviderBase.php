<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Constant\ExternalProviderSetting;
use AppBundle\Service\CacheService;

class ExternalProviderBase
{
    /** @var ExternalProviderAuthenticator */
    private $authenticator;

    /** @var int */
    private $retryCount;

    /** @var CacheService */
    private $cacheService;

    private $customerListCacheId;

    private $officeListCacheId;

    public function __construct(ExternalProviderAuthenticator $authenticator, CacheService $cacheService)
    {
        $this->authenticator = $authenticator;
        $this->resetRetryCount();
        $this->cacheService = $cacheService;
    }

    /**
     * @return ExternalProviderAuthenticator
     */
    public function getAuthenticator(): ExternalProviderAuthenticator
    {
        return $this->authenticator;
    }

    /**
     * @return CacheService
     */
    public function getCacheService(): CacheService
    {
        return $this->cacheService;
    }

    /**
     * @return mixed
     */
    public function getCustomerListCacheId()
    {
        return $this->customerListCacheId;
    }

    /**
     * @param mixed $customerListCacheId
     */
    public function setCustomerListCacheId($customerListCacheId): void
    {
        $this->customerListCacheId = $customerListCacheId;
    }

    /**
     * @return mixed
     */
    public function getOfficeListCacheId()
    {
        return $this->officeListCacheId;
    }

    /**
     * @param mixed $officeListCacheId
     */
    public function setOfficeListCacheId($officeListCacheId): void
    {
        $this->officeListCacheId = $officeListCacheId;
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