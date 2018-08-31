<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Component\ExternalProvider\ApiConnectors\OfficeApiConnector;
use AppBundle\Util\ResultUtil;
use PhpTwinfield\Secure\WebservicesAuthentication;

class ExternalProviderOfficeService
{
    private $authenticationConnection;
    /** @var OfficeApiConnector $officeConnection */
    private $officeConnection;

    public function instantiateServices( $twinfieldUser, $twinfieldPassword, $twinfieldOrganisation) {
        $this->authenticationConnection = new WebservicesAuthentication($twinfieldUser, $twinfieldPassword, $twinfieldOrganisation);
        $this->officeConnection = new OfficeApiConnector($this->authenticationConnection);
    }

    public function getAllOfficesResponse() {
        return ResultUtil::successResult($this->officeConnection->listAll());
    }

    public function getAllOffices() {
        return $this->officeConnection->listAll();
    }
}