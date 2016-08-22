<?php

namespace AppBundle\Validation;


use AppBundle\Entity\Animal;
use AppBundle\Entity\Client;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;

class Validator
{
    /**
     * validate if Id is of format: AZ123456789
     *
     * @param $ulnString
     * @return bool
     */
    public static function verifyUlnFormat($ulnString)
    {
        $countryCodeLength = 2;
        $numberLength = 12;
        $ulnLength = $countryCodeLength + $numberLength;

        if(preg_match("/([A-Z]{2})+([0-9]{12})/",$ulnString)
            && strlen($ulnString) == $ulnLength) {
            return true;
        } else {
            return false;
        }
    }
    
}