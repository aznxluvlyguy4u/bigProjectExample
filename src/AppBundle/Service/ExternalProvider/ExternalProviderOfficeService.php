<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Component\ExternalProvider\ApiConnectors\OfficeApiConnector;
use AppBundle\Util\ResultUtil;

class ExternalProviderOfficeService extends ExternalProviderBase implements ExternalProviderInterface
{
    /** @var OfficeApiConnector $officeConnection */
    private $officeConnection;

    /**
     * @required
     */
    public function reAuthenticate() {
        $this->getAuthenticator()->refreshConnection();
        $this->officeConnection = new OfficeApiConnector($this->getAuthenticator()->getConnection());
    }

    public function getAllOfficesResponse() {
        return ResultUtil::successResult($this->officeConnection->listAll());
    }

    public function getAllOffices() {
        return $this->officeConnection->listAll();
    }
}