<?php


namespace AppBundle\Service;


use AppBundle\Component\HttpFoundation\JsonResponse;
use AppBundle\Component\Utils;
use AppBundle\Controller\ProfileAPIControllerInterface;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\FormInput\CompanyProfile;
use AppBundle\Output\LoginOutput;
use AppBundle\Util\ActionLogWriter;
use AppBundle\Util\RequestUtil;
use AppBundle\Util\ResultUtil;
use Symfony\Component\HttpFoundation\Request;


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
        $content = RequestUtil::getContentAsArray($request);
        $location = $this->getSelectedLocation($request);

        $this->nullCheckClient($client);
        $this->nullCheckLocation($location);

        //TODO Phase 2: Give back a specific company and location of that company. The CompanyProfileOutput already can process a ($client, $company, $location) method signature.
        $company = $location->getCompany();

        //Persist updated changes and return the updated values
        $client = CompanyProfile::update($client, $content, $company);
        $this->getManager()->persist($client);
        $log = ActionLogWriter::updateProfile($this->getManager(), $client, $loggedInUser, $company);
        $this->flushClearAndGarbageCollect(); //Only flush after persisting both the client and ActionLogWriter

        return $this->getCompanyProfileOutput($company, $location);
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