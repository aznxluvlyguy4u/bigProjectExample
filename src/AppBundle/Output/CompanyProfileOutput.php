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
            "company" =>
                array(
                    "company_name" => $company->getCompanyName(),
                    "telephone_number" => $company->getTelephoneNumber(),
                    "ubn" => $location->getUbn(), //TODO verify Uniek bedrijfsnummber === ubn
                    "btw_number" => $company->getBtwNumber(),
                    "kvk_number" => $company->getKvkNumber(),
                    "brs_number" => $company->getBrsNumber(),
                    "billing_address" =>
                        array(
                            "street_name" => $billingAddress->getStreetName(),
                            "address_number" => $billingAddress->getAddressNumber(),
                            "postal_code" => $billingAddress->getPostalCode(),
                            "city" => $billingAddress->getCity()
                        )
                ),
            "location" =>
                array(
                    "street_name" => $address->getStreetName(),
                    "address_number" => $address->getAddressNumber(),
                    "postal_code" => $address->getPostalCode(),
                    "city" => $address->getCity()
                ),
            "client" =>
                array(
                    "first_name" => $client->getFirstName(),
                    "last_name" => $client->getLastName(),
                    "cellphone_number" => $client->getCellphoneNumber()
                )
        );

        return $result;
    }



}