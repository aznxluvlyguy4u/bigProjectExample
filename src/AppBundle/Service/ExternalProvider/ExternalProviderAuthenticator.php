<?php


namespace AppBundle\Service\ExternalProvider;

use PhpTwinfield\Secure\WebservicesAuthentication;

/**
 * Class ExternalProviderAuthenticator
 */
class ExternalProviderAuthenticator
{
    /** @var WebservicesAuthentication */
    private $authenticationConnection;
    /** @var string */
    private $twinfieldUser;
    /** @var string */
    private $twinfieldPassword;
    /** @var string */
    private $twinfieldOrganisation;


    /**
     * ExternalProviderAuthenticator constructor.
     * @param string $twinfieldUser
     * @param string $twinfieldPassword
     * @param string $twinfieldOrganisation
     */
    public function __construct($twinfieldUser, $twinfieldPassword, $twinfieldOrganisation)
    {
        $this->twinfieldUser = $twinfieldUser;
        $this->twinfieldPassword = $twinfieldPassword;
        $this->twinfieldOrganisation = $twinfieldOrganisation;
    }

    /**
     * @return WebservicesAuthentication
     */
    public function getConnection(): WebservicesAuthentication
    {
        if (!$this->authenticationConnection) {
            $this->refreshConnection();
        }
        return $this->authenticationConnection;
    }

    /**
     * @return ExternalProviderAuthenticator
     */
    public function refreshConnection(): ExternalProviderAuthenticator
    {
        $this->authenticationConnection = new WebservicesAuthentication(
            $this->twinfieldUser, $this->twinfieldPassword, $this->twinfieldOrganisation
        );
        return $this;
    }
}