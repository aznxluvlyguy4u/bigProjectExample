<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Component\ExternalProvider\ApiConnectors\OfficeApiConnector;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @return array|\PhpTwinfield\Office[]
     * @throws \Exception
     */
    public function getAllOffices() {
        try {
            return $this->officeConnection->listAll();
        } catch (\Exception $exception) {
            if (!$this->allowRetryTwinfieldApiCall($exception)) {
                $this->resetRetryCount();
                throw new \Exception($exception->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->incrementRetryCount();
            $this->reAuthenticate();
            return $this->getAllOffices();
        }
    }
}