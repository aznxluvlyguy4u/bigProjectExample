<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Controller\ProfileAPIControllerInterface;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Company;
use AppBundle\Entity\CompanyAddress;
use AppBundle\Entity\Country;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\AccessLevelType;
use AppBundle\Output\LoginOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Validation\AdminValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;


class ProfileService extends ControllerServiceBase implements ProfileAPIControllerInterface
{
    /**
     * @param Request $request
     * @return array
\     */
    public function getCompanyProfile(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        return $this->getCompanyProfileOutput($location->getCompany(), $location);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getLoginData(Request $request)
    {
        $loggedInUser = $this->getUser();
        $client = $this->getAccountOwner($request);
        $this->nullCheckClient($client);
        $revealHistoricAnimals = $this->getSelectedLocation($request)->getCompany()->getIsRevealHistoricAnimals();
        $outputArray = LoginOutput::create($client, $loggedInUser, $revealHistoricAnimals);
        return ResultUtil::successResult($outputArray);
    }


    /**
     * @param Request $request
     * @return array
     */
    public function editCompanyProfile(Request $request)
    {
        $client = $this->getAccountOwner($request);
        $loggedInUser = $this->getUser();
        $content = RequestUtil::getContentAsArrayCollection($request);
        $location = $this->getSelectedLocation($request);

        $isAdmin = AdminValidator::isAdmin($loggedInUser, AccessLevelType::ADMIN);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        //TODO Phase 2: Give back a specific company and location of that company. The CompanyProfileOutput already can process a ($client, $company, $location) method signature.
        $company = $location->getCompany();

        //Persist updated changes and return the updated values
        $billingAddress = $company->getBillingAddress();
        $address = $company->getAddress();

        $billingAddressArray = $content->get('billing_address');
        $addressArray = $content->get('address');
        $contactPersonArray = $content->get('contact_person');

        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));
        //NOTE! Don't let the user change their UBN by themselves!
        //If they change it to the UBN of another user, they can edit their data!
        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
//        $company->setCompanyRelationNumber($content->get('company_relation_number'));
//        in the front-end company_relation_number refers to the client 'relationNumberKeeper'.

        $billingAddress->setStreetName($billingAddressArray['street_name']);
        $billingAddress->setAddressNumberSuffix(ArrayUtil::get('suffix', $billingAddressArray, null));
        $billingAddress->setAddressNumber($billingAddressArray['address_number']);
        $billingAddress->setPostalCode(strtoupper($billingAddressArray['postal_code']));
        $billingAddress->setCity(strtoupper($billingAddressArray['city']));
        $billingAddress->setState($billingAddressArray['state']);
        if ($isAdmin) {
            $this->updateCountryFromAddressArray($billingAddressArray,BillingAddress::class, $company);
        }

        $address->setStreetName($addressArray['street_name']);
        $address->setAddressNumberSuffix(ArrayUtil::get('suffix', $addressArray, null));
        $address->setAddressNumber($addressArray['address_number']);
        $address->setPostalCode(strtoupper($addressArray['postal_code']));
        $address->setCity(strtoupper($addressArray['city']));
        $address->setState($addressArray['state']);
        if ($isAdmin) {
            $this->updateCountryFromAddressArray($addressArray,CompanyAddress::class, $company);
        }

        $company->getOwner()->setFirstName($contactPersonArray['first_name']);
        $company->getOwner()->setLastName($contactPersonArray['last_name']);
        $company->getOwner()->setCellphoneNumber($contactPersonArray['cellphone_number']);

        $veterinarianArray = $content->get('veterinarian');
        $company->setVeterinarianDapNumber($veterinarianArray['dap_number']);
        $company->setVeterinarianCompanyName($veterinarianArray['company_name']);
        $company->setVeterinarianTelephoneNumber($veterinarianArray['telephone_number']);
        $company->setVeterinarianEmailAddress(strtolower($veterinarianArray['email_address']));

        $isRevealHistoricAnimals = Utils::getNullCheckedArrayCollectionValue(JsonInputConstant::IS_REVEAL_HISTORIC_ANIMALS, $content);
        if($isRevealHistoricAnimals !== null) {
            $company->setIsRevealHistoricAnimals($isRevealHistoricAnimals);
        }


        $this->getManager()->persist($client);
        $log = ActionLogWriter::updateProfile($this->getManager(), $client, $loggedInUser, $company);
        $this->flushClearAndGarbageCollect(); //Only flush after persisting both the client and ActionLogWriter

        return $this->getCompanyProfileOutput($company, $location);
    }


    /**
     * @param array $addressArray
     * @param $addressClazz
     * @param Company $company
     * @return Company
     */
    private function updateCountryFromAddressArray(array $addressArray, $addressClazz, Company $company)
    {
        switch ($addressClazz) {
            case CompanyAddress::class: $currentCountryId = $company->getAddressCountryId(); break;
            case BillingAddress::class: $currentCountryId = $company->getBillingAddressCountryId(); break;
            default: throw new PreconditionFailedHttpException('Invalid address clazz '.$addressClazz);
        }

        $addressArray[JsonInputConstant::TYPE] = StringUtil::getEntityName($addressClazz);

        $countryId = ArrayUtil::getNestedValue(
            [
                JsonInputConstant::COUNTRY,
                JsonInputConstant::ID,
            ],
            $addressArray
        );
        if (!$countryId) {
            throw new PreconditionFailedHttpException('id is missing for Country of '.$addressClazz);
        }

        if ($countryId === $currentCountryId) {
            return $company;
        }

        $country = $this->getManager()->getRepository(Country::class)->find($countryId);
        if (!$country) {
            throw new PreconditionFailedHttpException('No country found with id '.$countryId);
        }

        switch ($addressClazz) {
            case CompanyAddress::class: $company->getAddress()->setCountryDetails($country); break;
            case BillingAddress::class: $company->getBillingAddress()->setCountryDetails($country); break;
            default: throw new PreconditionFailedHttpException('Invalid address clazz '.$addressClazz);
        }

        return $company;
    }




    /**
     * @param Company $company
     * @param Location $location
     * @return array
     */
    private function getCompanyProfileOutput(Company $company, Location $location)
    {
        $billingAddress = $company->getBillingAddress();
        $address = $company->getAddress();
        $owner = $company->getOwner();

        return [
            "company_name" => Utils::fillNull($company->getCompanyName()),
            "telephone_number" => Utils::fillNull($company->getTelephoneNumber()),
            "ubn" => Utils::fillNull($location->getUbn()),
            "vat_number" => Utils::fillNull($company->getVatNumber()),
            "chamber_of_commerce_number" => Utils::fillNull($company->getChamberOfCommerceNumber()),
            "company_relation_number" => Utils::fillNull($owner->getRelationNumberKeeper()),
            "billing_address" =>
                [
                    "street_name" => Utils::fillNull($billingAddress->getStreetName()),
                    "suffix" => Utils::fillNull($billingAddress->getAddressNumberSuffix()),
                    "address_number" => Utils::fillNull($billingAddress->getAddressNumber()),
                    "postal_code" => Utils::fillNull($billingAddress->getPostalCode()),
                    "city" => Utils::fillNull($billingAddress->getCity()),
                    "state" => Utils::fillNull($billingAddress->getState()),
                    "country" => $billingAddress->getCountryDetails(),
                ],
            "address" =>
                [
                    "street_name" => Utils::fillNull($address->getStreetName()),
                    "address_number" => $address->getAddressNumber(), //this is an integer
                    "suffix" => Utils::fillNull($address->getAddressNumberSuffix()),
                    "postal_code" => Utils::fillNull($address->getPostalCode()),
                    "city" => Utils::fillNull($address->getCity()),
                    "state" => Utils::fillNull($address->getState()),
                    "country" => $address->getCountryDetails(),
                ],
            "contact_person" =>
                [
                    "first_name" => Utils::fillNull($owner->getFirstName()),
                    "last_name" => Utils::fillNull($owner->getLastName()),
                    "cellphone_number" => Utils::fillNull($owner->getCellphoneNumber()),
                ],
            "veterinarian" =>
                [
                    "dap_number" => Utils::fillNull($company->getVeterinarianDapNumber()),
                    "company_name" => Utils::fillNull($company->getVeterinarianCompanyName()),
                    "telephone_number" => Utils::fillNull($company->getVeterinarianTelephoneNumber()),
                    "email_address" => Utils::fillNull($company->getVeterinarianEmailAddress()),
                ],
            "is_reveal_historic_animals" => Utils::fillNull($company->getIsRevealHistoricAnimals()),
        ];
    }
}
