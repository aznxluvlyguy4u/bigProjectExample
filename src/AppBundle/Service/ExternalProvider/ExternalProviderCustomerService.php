<?php


namespace AppBundle\Service\ExternalProvider;


use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\Customer;
use PhpTwinfield\Office;
use Symfony\Component\HttpFoundation\Response;

class ExternalProviderCustomerService extends ExternalProviderBase implements ExternalProviderInterface
{
    /** @var CustomerApiConnector */
    private $customerConnection;
    /** @var ExternalProviderOfficeService */
    private $twinfieldOfficeService;


    /**
     * @required
     *
     * @param ExternalProviderOfficeService $officeService
     */
    public function setExternalProviderOfficeService(ExternalProviderOfficeService $officeService) {
        $this->twinfieldOfficeService = $officeService;
    }

    /**
     * @required
     */
    public function reAuthenticate() {
        $this->getAuthenticator()->refreshConnection();
        $this->customerConnection = new CustomerApiConnector($this->getAuthenticator()->getConnection());
    }


    /**
     * @param $officeCode
     * @return array
     * @throws \Exception
     */
    public function getAllCustomers($officeCode) {
        $office = new Office();
        $office->setCode($officeCode);

        $this->resetRetryCount();
        $result = $this->listAllOffices($office);
        $resultWithCode = [];
        foreach ($result as $key => $customer) {
            $customer['code'] = $key;
            $resultWithCode[] = $customer;
        }
        return $resultWithCode;
    }

    /**
     * @param Office $office
     * @return array
     * @throws \Exception
     */
    private function listAllOffices(Office $office): array
    {
        try {
            return $this->customerConnection->listAll($office);
        } catch (\Exception $exception) {
            if (!$this->allowRetryTwinfieldApiCall($exception)) {
                $this->resetRetryCount();
                throw new \Exception($exception->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->incrementRetryCount();
            $this->reAuthenticate();
            return $this->listAllOffices($office);
        }
    }

    /**
     * @param $debtorNumber
     * @param $administrationCode
     * @return \AppBundle\Component\HttpFoundation\JsonResponse|Customer
     * @throws \Exception
     */
    public function getSingleCustomer($debtorNumber, $administrationCode) {
        $offices = $this->twinfieldOfficeService->getAllOffices();
        $customerOffice = new Office();
        if (!is_array($offices) || empty($offices) || !is_a($offices[0], Office::class)) {
            throw new \Exception("ExternalProvider office call failed", Response::HTTP_NOT_FOUND);
        }
        /** @var Office $office */
        foreach ($offices as $office) {
            if ($office->getCode() == $administrationCode) {
                $customerOffice = $office;
            }

        }
        $this->resetRetryCount();
        return $this->getCustomer($debtorNumber, $customerOffice);
    }


    /**
     * @param string $debtorNumber
     * @param Office $customerOffice
     * @return Customer
     * @throws \Exception
     */
    private function getCustomer(string $debtorNumber, Office $customerOffice): Customer
    {
        try {
            return $this->customerConnection->get($debtorNumber, $customerOffice);
        } catch (\Exception $exception) {
            if (!$this->allowRetryTwinfieldApiCall($exception)) {
                $this->resetRetryCount();
                throw new \Exception($exception->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->incrementRetryCount();
            $this->reAuthenticate();
            return $this->getCustomer($debtorNumber, $customerOffice);
        }
    }

}