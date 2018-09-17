<?php

namespace AppBundle\Entity;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\JmsGroup;
use AppBundle\Service\BaseSerializer;
use AppBundle\Service\CacheService;

/**
 * Class CountryRepository
 * @package AppBundle\Entity
 */
class CountryRepository extends BaseRepository {

    const GET_ALL_COUNTRIES = 'GET_ALL_COUNTRIES';

    /**
     * @param BaseSerializer $serializer
     * @param CacheService $cacheService
     * @param array $jmsGroups
     * @return Country[]|array
     */
    function getAll(BaseSerializer $serializer, CacheService $cacheService, $jmsGroups = [JmsGroup::BASIC])
    {
        $cacheId = self::GET_ALL_COUNTRIES . '_' . implode('-', $jmsGroups);

        if ($cacheService->isHit($cacheId)) {
            $countries =  $serializer->deserializeArrayOfObjects($cacheService->getItem($cacheId), Country::class);
        } else {
            $countries = $this->findAll();
            $serializedCountries = $serializer->getArrayOfSerializedObjects($countries, $jmsGroups, true);
            $countries = $serializer->deserializeArrayOfObjects($serializedCountries, Country::class);

            $cacheService->set($cacheId, $serializedCountries);
        }
        return $countries;
    }


    /**
     * @param Location $location
     * @return string|null
     * @throws \Doctrine\DBAL\DBALException
     */
    function getCountryFromLocation(Location $location): ?string
    {
        if (!$location || !is_int($location->getId())) {
            return null;
        }

        $sql = "SELECT
                  l.ubn,
                  cd.code as ".JsonInputConstant::COUNTRY_CODE."
                FROM location l
                  INNER JOIN address a ON l.address_id = a.id
                  LEFT JOIN country cd ON cd.name = a.country
                WHERE l.id = ".$location->getId();
        return $this->getManager()->getConnection()->query($sql)->fetch()[JsonInputConstant::COUNTRY_CODE];
    }


    /**
     * @param Location $location
     * @param bool $nullIsDutch
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    function isDutchLocation(Location $location, $nullIsDutch = true): bool
    {
        $countryCode = $this->getCountryFromLocation($location);
        return $countryCode ? $countryCode === \AppBundle\Enumerator\Country::NL : $nullIsDutch;
    }
}