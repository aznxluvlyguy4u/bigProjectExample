<?php


namespace AppBundle\Validation;


use Doctrine\DBAL\Connection;

class AnimalResidenceValidator
{
    /**
     * @param Connection $connection
     * @param string $countryCode
     * @return bool
     */
    public static function isValidCountryCode(Connection $connection, $countryCode)
    {
        if (!is_string($countryCode)
            || strtr(strtoupper($countryCode), [' ' => '']) !== $countryCode
            || strlen($countryCode) !== 2
        ) {
            return false;
        }

        $sql = "SELECT
                  COUNT(*) > 0 as country_code_exists
                FROM country WHERE code = '".$countryCode."'";
        return $connection->query($sql)->fetch()['country_code_exists'];
    }
}