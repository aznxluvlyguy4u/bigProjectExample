<?php


namespace AppBundle\Service\Twinfield;


use AppBundle\Util\ResultUtil;
use PhpTwinfield\ApiConnectors\OfficeApiConnector;
use PhpTwinfield\Secure\WebservicesAuthentication;

class TwinfieldOfficeService
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