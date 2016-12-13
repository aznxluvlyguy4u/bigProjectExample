<?php

namespace AppBundle\Output;


use AppBundle\Component\Utils;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\TagStateType;

/**
 * Class DeclareAnimalDetailsOutput
 */
class CompanyProfileOutput extends Output
{
    /**
     * @param Client $client
     * @param Company $company
     * @param Location $location
     * @return array
     */
    public static function create(Client $client, Company $company, Location $location)
    {
        $billingAddress = $company->getBillingAddress();
        $address = $company->getAddress();
        $owner = $company->getOwner();

        $result = array(
                    "company_name" => Utils::fillNull($company->getCompanyName()),
                    "telephone_number" => Utils::fillNull($company->getTelephoneNumber()),
                    "ubn" => Utils::fillNull($location->getUbn()),
                    "vat_number" => Utils::fillNull($company->getVatNumber()),
                    "chamber_of_commerce_number" => Utils::fillNull($company->getChamberOfCommerceNumber()),
                    "company_relation_number" => Utils::fillNull($owner->getRelationNumberKeeper()),
                    "billing_address" =>
                        array(
                            "street_name" => Utils::fillNull($billingAddress->getStreetName()),
                            "suffix" => Utils::fillNull($billingAddress->getAddressNumberSuffix()),
                            "address_number" => Utils::fillNull($billingAddress->getAddressNumber()),
                            "postal_code" => Utils::fillNull($billingAddress->getPostalCode()),
                            "city" => Utils::fillNull($billingAddress->getCity()),
                            "state" => Utils::fillNull($billingAddress->getState())
                        ),
                    "address" =>
                        array(
                            "street_name" => Utils::fillNull($address->getStreetName()),
                            "address_number" => $address->getAddressNumber(), //this is an integer
                            "suffix" => Utils::fillNull($address->getAddressNumberSuffix()),
                            "postal_code" => Utils::fillNull($address->getPostalCode()),
                            "city" => Utils::fillNull($address->getCity()),
                            "state" => Utils::fillNull($address->getState()
                        )),
                    "contact_person" =>
                        array(
                            "first_name" => Utils::fillNull($owner->getFirstName()),
                            "last_name" => Utils::fillNull($owner->getLastName()),
                            "cellphone_number" => Utils::fillNull($owner->getCellphoneNumber())
                        ),
                    "veterinarian" =>
                        array(
                            "dap_number" => Utils::fillNull($company->getVeterinarianDapNumber()),
                            "company_name" => Utils::fillNull($company->getVeterinarianCompanyName()),
                            "telephone_number" => Utils::fillNull($company->getVeterinarianTelephoneNumber()),
                            "email_address" => Utils::fillNull($company->getVeterinarianEmailAddress())
                        ),
                    "is_reveal_historic_animals" => Utils::fillNull($company->getIsRevealHistoricAnimals())
                );

        return $result;
    }



}