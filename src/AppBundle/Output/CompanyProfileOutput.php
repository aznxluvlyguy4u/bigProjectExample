<?php

namespace AppBundle\Output;


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

        $result = array(
                    "company_name" => self::fillNull($company->getCompanyName()),
                    "telephone_number" => self::fillNull($company->getTelephoneNumber()),
                    "ubn" => self::fillNull($location->getUbn()),
                    "vat_number" => self::fillNull($company->getVatNumber()),
                    "chamber_of_commerce_number" => self::fillNull($company->getChamberOfCommerceNumber()),
                    "company_relation_number" => self::fillNull($company->getCompanyRelationNumber()),
                    "billing_address" =>
                        array(
                            "street_name" => self::fillNull($billingAddress->getStreetName()),
                            "suffix" => self::fillNull($billingAddress->getAddressNumberSuffix()),
                            "address_number" => self::fillNull($billingAddress->getAddressNumber()),
                            "postal_code" => self::fillNull($billingAddress->getPostalCode()),
                            "city" => self::fillNull($billingAddress->getCity()),
                            "state" => self::fillNull($billingAddress->getState())
                        ),
                    "address" =>
                        array(
                            "street_name" => self::fillNull($address->getStreetName()),
                            "address_number" => $address->getAddressNumber(), //this is an integer
                            "suffix" => self::fillNull($address->getAddressNumberSuffix()),
                            "postal_code" => self::fillNull($address->getPostalCode()),
                            "city" => self::fillNull($address->getCity()),
                            "state" => self::fillNull($address->getState()
                        )),
                    "contact_person" =>
                        array(
                            "first_name" => self::fillNull($client->getFirstName()),
                            "last_name" => self::fillNull($client->getLastName()),
                            "cellphone_number" => self::fillNull($client->getCellphoneNumber())
                        ),
                    "veterinarian" =>
                        array(
                            "dap_number" => self::fillNull($company->getVeterinarianDapNumber()),
                            "company_name" => self::fillNull($company->getVeterinarianCompanyName()),
                            "telephone_number" => self::fillNull($company->getVeterinarianTelephoneNumber()),
                            "email_address" => self::fillNull($company->getVeterinarianEmailAddress())
                        )
                );

        return $result;
    }



}