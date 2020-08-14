<?php


namespace AppBundle\Service\ExternalProvider;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Constant\ExternalProviderSetting;
use Doctrine\Common\Collections\ArrayCollection;
use PhpTwinfield\ApiConnectors\CustomerApiConnector;
use PhpTwinfield\Request\Read\Customer;
use PhpTwinfield\Office;
use SoapClient;
use SoapFault;
use SoapHeader;
use Symfony\Component\HttpFoundation\Response;

class ExternalProviderCustomerService extends ExternalProviderBase implements ExternalProviderInterface
{
    /** @var CustomerApiConnector */
    private $customerConnection;
    /** @var ExternalProviderOfficeService */
    private $twinfieldOfficeService;

    /** @var string $external_provider_user */
    private $external_provider_user;

    /** @var string $external_provider_password */
    private $external_provider_password;

    /** @var string $external_provider_organisation */
    private $external_provider_organisation;

    /** @var string $office_code */
    private $office_code;

    /**
     * @required
     *
     * @param ExternalProviderOfficeService $officeService
     * @param $external_provider_user
     * @param $external_provider_password
     * @param $external_provider_organisation
     * @param $office_code
     */
    public function setProperties(
        ExternalProviderOfficeService $officeService,
        $external_provider_user,
        $external_provider_password,
        $external_provider_organisation,
        $office_code
    ) {
        $this->twinfieldOfficeService = $officeService;
        $this->external_provider_user = $external_provider_user;
        $this->external_provider_password = $external_provider_password;
        $this->external_provider_organisation = $external_provider_organisation;
        $this->office_code = $office_code;
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
     * @return JsonResponse|Customer
     * @throws \Exception
     */
    public function getSingleCustomer($debtorNumber, $administrationCode) {
        if ($this->getOfficeListCacheId() && $this->getCacheService()->isHit($this->getOfficeListCacheId())) {
            $offices = $this->getCacheService()->getItem($this->getOfficeListCacheId());
        } else {
            $offices = $this->twinfieldOfficeService->getAllOffices();
            if (!is_array($offices) || empty($offices) || !is_a($offices[0], Office::class)) {
                throw new \Exception("ExternalProvider office call failed", Response::HTTP_NOT_FOUND);
            }
            $officeCacheId = ExternalProviderSetting::EXTERNAL_PROVIDER_OFFICE_LIST_CACHE_ID;
            $this->getCacheService()->set($officeCacheId, $offices);
            $this->setOfficeListCacheId($officeCacheId);
        }

        $customerOffice = new Office();
        if (!is_array($offices) || empty($offices) || !is_a($offices[0], Office::class)) {
            throw new \Exception("ExternalProvider office call failed", Response::HTTP_NOT_FOUND);
        }
        $customerList = null;
        /** @var Office $office */
        foreach ($offices as $office) {
            if ($office->getCode() == $administrationCode) {
                $customerOffice = $office;
                $customersCacheId = $office->getCode()
                    . ExternalProviderSetting::EXTERNAL_PROVIDER_OFFICE_CUSTOMER_POSTFIX_CACHE_ID;
                if (!$this->getCacheService()->isHit($customersCacheId)) {

                    $customerList = $this->getAllCustomers($office->getCode());
                    $this->getCacheService()->set($customersCacheId, $customerList);
                } else {
                    $customerList = $this->getCacheService()->getItem($customersCacheId);
                }
            }
        }
        if ($customerList !== null && is_array($customerList)) {
            foreach ($customerList as $customer) {
                if ($customer["code"] == $debtorNumber) {
                    return $this->getCustomer($debtorNumber, $customerOffice);
                }
            }
        }

        $this->resetRetryCount();

        return null;
    }

    /**
     * @param ArrayCollection $content
     * @param $countryCode
     * @return array
     * @throws SoapFault If you need to edit send a existing code in the request
     */
    public function createOrEditCustomer(ArrayCollection $content, $countryCode)
    {
        $code = rand(1111111, 9999999);

        if ($content->containsKey('twinfield_code')) {
            $code = $content->get('twinfield_code');
        }

        $contentBillingAddress = $content->get('billing_address');
        $contentOwner = $content->get('owner');

        $soapClient = new SoapClient("https://login.twinfield.com/webservices/session.asmx?wsdl", ["trace" => 1]);

        $params = [
            'user' => $this->external_provider_user,
            'password' => $this->external_provider_password,
            'organisation' => $this->external_provider_organisation
        ];

        $soapClient->Logon($params);
        $p = xml_parser_create();
        xml_parse_into_struct($p, $soapClient->__getLastResponse(), $vals, $index);

        $sessionID = $vals[3]['value'];

        $processXMLClient = new SoapClient("https://accounting2.twinfield.com/webservices/processxml.asmx?wsdl", ["trace" => 1]);

        $headerData = ["SessionID" => $sessionID, "CompanyCode" => $this->office_code];

        $header = new SoapHeader('http://www.twinfield.com/','Header',
            $headerData);

        $processXMLClient->__setSoapHeaders($header);

        $addressXml = '
           <addresses>
                <address default="true" type="invoice">
                    <name>'.$content->get('company_name').'</name>
                    <country>'.$countryCode.'</country>
                    <city>'.$contentBillingAddress['city'].'</city>
                    <postcode>'.$contentBillingAddress['postal_code'].'</postcode>
                    <telephone>'.$content->get('telephone_number').'</telephone>
                    <telefax />
                    <email>'.$contentOwner['email_address'].'</email>
                    <contact />
                    <field1 />
                    <field2>'.$contentBillingAddress['street_name'].' '.$contentBillingAddress['address_number'].'</field2>
                    <field3 />
                    <field4></field4>
                    <field5 />
                    <field6 />
                </address>
           </addresses>
        ';

        $xml = '
        <dimension status="active" result="1">
            <office>'.$this->office_code.'</office>
            <type name="Debiteuren" shortname="Debiteuren">DEB</type>
            <code>'.$code.'</code>
            <name>'.$content->get('company_name').'</name>
            '.$addressXml.'
        </dimension>
        ';

        $processXMLParams = [
            "xmlRequest" => $xml
        ];

        $array = json_decode(json_encode($processXMLClient->ProcessXmlString($processXMLParams)), true);

        $processedResult = $this->xmlToArray($array["ProcessXmlStringResult"]);

        return [
                "name" => $processedResult['name'],
                "code" => $processedResult['code']
            ];
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

    private function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml)), true);
    }

}