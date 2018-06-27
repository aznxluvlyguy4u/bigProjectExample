<?php


namespace AppBundle\Service\Twinfield;


use AppBundle\Util\ResultUtil;
use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\Exception;
use PhpTwinfield\Office;
use PhpTwinfield\Secure\WebservicesAuthentication;

class TwinfieldCustomerService
{
    private $authenticationConnection;
    /** @var CustomerApiConnector */
    private $customerConnection;
    /** @var TwinfieldOfficeService */
    private $twinfieldOfficeService;

    public function instantiateServices( $twinfieldUser, $twinfieldPassword, $twinfieldOrganisation, TwinfieldOfficeService $officeService) {
        $this->authenticationConnection = new WebservicesAuthentication($twinfieldUser, $twinfieldPassword, $twinfieldOrganisation);
        $this->customerConnection = new CustomerApiConnector($this->authenticationConnection);
        $this->twinfieldOfficeService = $officeService;
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
            return ResultUtil::errorResult($e->getMessage(), $e->getCode());
        }
    }

    public function getSingleCustomer($debtorNumber, $administrationCode) {
        $offices = $this->twinfieldOfficeService->getAllOffices();
        $customerOffice = new Office();
        if (!is_a($offices[0], Office::class)) {
            return ResultUtil::errorResult("Twinfield call failed", 404);
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
            return [$e->getCode(), $e->getMessage()];
        }
    }

}