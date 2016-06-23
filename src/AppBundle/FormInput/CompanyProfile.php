<?php

namespace AppBundle\FormInput;

use AppBundle\Constant\Constant;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use Doctrine\Common\Collections\ArrayCollection;

class CompanyProfile
{
    /**
     * @param Client $client
     * @param ArrayCollection $content
     * @param Company $company
     * @param Location $location
     * @return Client
     */
    public static function update(Client $client, ArrayCollection $content, $company)
    {
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
        $company->setCompanyRelationNumber($content->get('company_relation_number'));

        $billingAddress->setStreetName($billingAddressArray['street_name']);
        $billingAddress->setAddressNumberSuffix($billingAddressArray['suffix']);
        $billingAddress->setAddressNumber($billingAddressArray['address_number']);
        $billingAddress->setPostalCode(strtoupper($billingAddressArray['postal_code']));
        $billingAddress->setCity(strtoupper($billingAddressArray['city']));
        $billingAddress->setState($billingAddressArray['state']);

        $address->setStreetName($addressArray['street_name']);
        $address->setAddressNumberSuffix($addressArray['suffix']);
        $address->setAddressNumber($addressArray['address_number']);
        $address->setPostalCode(strtoupper($addressArray['postal_code']));
        $address->setCity(strtoupper($addressArray['city']));
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