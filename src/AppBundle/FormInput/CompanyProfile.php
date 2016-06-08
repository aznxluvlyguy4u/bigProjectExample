<?php

namespace AppBundle\FormInput;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use Doctrine\Common\Collections\ArrayCollection;

class CompanyProfile
{
    /**
     * @param Client $client
     * @param ArrayCollection $content
     * @return Client
     */
    public static function update(Client $client, ArrayCollection $content)
    {
        $company = $client->getCompanies()->get(0);
        $location = $company->getLocations()->get(0);
        $billingAddress = $company->getBillingAddress();
        $address = $company->getAddress();

        $billingAddressArray = $content->get('billing_address');
        $addressArray = $content->get('address');
        $contactPersonArray = $content->get('contact_person');

        $company->setCompanyName($content->get('company_name'));
        $company->setTelephoneNumber($content->get('telephone_number'));
        $location->setUbn($content->get(Constant::UBN_NAMESPACE));
        $company->setVatNumber($content->get('vat_number'));
        $company->setChamberOfCommerceNumber($content->get('chamber_of_commerce_number'));
        $company->setCompanyRelationNumber($content->get('company_relation_number'));

        $billingAddress->setStreetName($billingAddressArray['street_name']);
        $billingAddress->setAddressNumberSuffix($billingAddressArray['suffix']);
        $billingAddress->setAddressNumber($billingAddressArray['address_number']);
        $billingAddress->setPostalCode($billingAddressArray['postal_code']);
        $billingAddress->setCity($billingAddressArray['city']);
        $billingAddress->setState($billingAddressArray['state']);

        $address->setStreetName($addressArray['street_name']);
        $address->setAddressNumberSuffix($addressArray['suffix']);
        $address->setAddressNumber($addressArray['address_number']);
        $address->setPostalCode($addressArray['postal_code']);
        $address->setCity($addressArray['city']);
        $address->setState($addressArray['state']);

        $client->setFirstName($contactPersonArray['first_name']);
        $client->setLastName($contactPersonArray['last_name']);
        $client->setCellphoneNumber($contactPersonArray['cellphone_number']);

        $veterinarianArray = $content->get('veterinarian');
        $company->setVeterinarianDapNumber($veterinarianArray['dap_number']);
        $company->setVeterinarianCompanyName($veterinarianArray['company_name']);
        $company->setVeterinarianTelephoneNumber($veterinarianArray['telephone_number']);
        $company->setVeterinarianEmailAddress($veterinarianArray['email_address']);
        
        return $client;
    }
}