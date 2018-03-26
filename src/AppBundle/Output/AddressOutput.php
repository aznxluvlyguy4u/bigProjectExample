<?php

namespace AppBundle\Output;


use AppBundle\Entity\Address;

class AddressOutput
{
    /**
     * @param Address $address
     * @return array
     */
    public static function createAddressOutput($address) {
        return array(
            'id' => $address->getId(),
            'street_name' => $address->getStreetName(),
            'address_number' => $address->getAddressNumber(),
            'address_number_suffix' => $address->getAddressNumberSuffix(),
            'postal_code' => $address->getPostalCode(),
            'city' => $address->getCity(),
            'country' => $address->getCountry()
        );
    }
}