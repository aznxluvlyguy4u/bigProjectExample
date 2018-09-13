<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Util\ResultUtil;
use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\Exception;
use PhpTwinfield\Office;

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


    public function getAllCustomers($officeCode) {
        $office = new Office();
        $office->setCode($officeCode);
        try {
            $result = $this->customerConnection->listAll($office);
            $resultWithCode = [];
            foreach ($result as $key => $customer) {
                $customer['code'] = $key;
                $resultWithCode[] = $customer;
            }
            return ResultUtil::successResult($resultWithCode);
        } catch (Exception $e) {
            return ResultUtil::errorResult($e, 500);
        }
    }

    public function getSingleCustomer($debtorNumber, $administrationCode) {
        $offices = $this->twinfieldOfficeService->getAllOffices();
        $customerOffice = new Office();
        if (!is_array($offices) || empty($offices) || !is_a($offices[0], Office::class)) {
            return ResultUtil::errorResult("ExternalProvider office call failed", 404);
        }
        /** @var Office $office */
        foreach ($offices as $office) {
            if ($office->getCode() == $administrationCode) {
                $customerOffice = $office;
            }

        }
        try {
            return $this->customerConnection->get($debtorNumber, $customerOffice);
        } catch (Exception $e) {
            return $e;
        }
    }

}