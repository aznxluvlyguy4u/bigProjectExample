<?php

namespace AppBundle\Output;


use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\TagStateType;

/**
 * Class DeclareAnimalDetailsOutput
 */
class CompanyProfileOutput
{
    /**
     * @param Client $client
     * @param Company $company
     * @param Location $location
     * @return array
     */
    public static function create(Client $client, Company $company = null, Location $location = null)
    {
        if($company == null) {
            $company = $client->getCompanies()->get(0);
        }

        if($location == null) {
            $location = $company->getLocations()->get(0);
        }

        $billingAddress = $company->getBillingAddress();
        $address = $company->getAddress();

        $result = array(
                    "company_name" => $company->getCompanyName(),
                    "telephone_number" => $company->getTelephoneNumber(),
                    "ubn" => $location->getUbn(),
                    "vat_number" => $company->getVatNumber(),
                    "chamber_of_commerce_number" => $company->getChamberOfCommerceNumber(),
                    "company_relation_number" => $company->getCompanyRelationNumber(),
                    "billing_address" =>
                        array(
                            "street_name" => $billingAddress->getStreetName(),
                            "suffix" => $billingAddress->getAddressNumberSuffix(),
                            "address_number" => $billingAddress->getAddressNumber(),
                            "postal_code" => $billingAddress->getPostalCode(),
                            "city" => $billingAddress->getCity()
                        ),
                    "address" =>
                        array(
                            "street_name" => $address->getStreetName(),
                            "address_number" => $address->getAddressNumber(),
                            "suffix" => $address->getAddressNumberSuffix(),
                            "postal_code" => $address->getPostalCode(),
                            "city" => $address->getCity()
                        ),
                    "contact_person" =>
                        array(
                            "first_name" => $client->getFirstName(),
                            "last_name" => $client->getLastName(),
                            "cellphone_number" => $client->getCellphoneNumber()
                        ),
                    "veterinarian" =>
                        array(
                            "dap_number" => $company->getVeterinarianDapNumber(),
                            "company_name" => $company->getVeterinarianCompanyName(),
                            "telephone_number" => $company->getVeterinarianTelephoneNumber(),
                            "email_address" => $company->getVeterinarianEmailAddress()
                        )
                );

        return $result;
    }



}