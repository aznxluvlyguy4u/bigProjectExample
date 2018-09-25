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
     * @param string $countryName
     * @return Country|null
     */
    function getCountryByName($countryName): ?Country
    {
        if (!is_string($countryName)) {
            return null;
        }
        return $this->findOneBy(['name' => trim($countryName)]);
    }


}