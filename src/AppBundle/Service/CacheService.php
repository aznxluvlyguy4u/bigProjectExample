<?php

namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class CacheService
 */
class CacheService
{
    const CACHE_LIFETIME_IN_SECONDS = 3600;

    /** @var string */
    private $redisHost;

    /** @var RedisAdapter */
    private $redisAdapter;


    /**
     * CacheService constructor.
     * @param string $redisHost
     */
    public function __construct($redisHost)
    {
        $this->redisHost = $redisHost;
        $this->getRedisAdapter();
    }


    /**
     * @return RedisAdapter
     */
    public function getRedisAdapter()
    {
        if (!$this->redisAdapter) {

            $client = RedisAdapter::createConnection(

                // provide a string dsn
                $this->redisHost,

                // associative array of configuration options
                array(
                    'persistent' => 0,
                    'persistent_id' => null,
                    'timeout' => 30,
                    'read_timeout' => 0,
                    'retry_interval' => 0,
                )
            );

            $this->redisAdapter = new RedisAdapter($client);
        }

        return $this->redisAdapter;
    }


    /**
     * @param string $cacheId
     * @return bool
     */
    public function isHit($cacheId)
    {
        $queryCache = $this->getRedisAdapter()->getItem($cacheId);
        return $queryCache->isHit();
    }


    /**
     * @param string $cacheId
     * @return mixed
     */
    public function getItem($cacheId)
    {
        $queryCache = $this->getRedisAdapter()->getItem($cacheId);
        return $queryCache->get();
    }



    public function set($cacheId, $values)
    {
        $queryCache = $this->getRedisAdapter()->getItem($cacheId);
        $queryCache->set($values);
        $queryCache->expiresAfter(self::CACHE_LIFETIME_IN_SECONDS);
        $this->getRedisAdapter()->save($queryCache);
        return $queryCache->get();

    }


    /**
     * @param string|array $cacheId as cacheId or array of cacheIds
     * @return bool
     */
    public function delete($cacheId)
    {
        if (is_array($cacheId)) {
            return $this->getRedisAdapter()->deleteItems($cacheId);
        } else {
            return $this->getRedisAdapter()->deleteItem($cacheId);
        }
    }


    /**
     * Clears the redis cache for the Livestock of a given location , to reflect changes of animals on Livestock.
     *
     * @param Location $location
     * @param Animal | Ewe | Ram | Neuter $animal
     */
    public function clearLivestockCacheForLocation(?Location $location = null, $animal = null) {
        if(!$location && $animal) {
            /** @var Location $location */
            $location = $animal->getLocation();
        }

        if($location) {
            $cacheId = AnimalRepository::LIVESTOCK_CACHE_ID .$location->getId();
            $historicCacheId = AnimalRepository::HISTORIC_LIVESTOCK_CACHE_ID .$location->getId();
            $candidateMotherId = AnimalRepository::CANDIDATE_MOTHERS_CACHE_ID . $location->getId();
            $eweLivestockWithLastMateCacheId = AnimalRepository::getEwesLivestockWithLastMateCacheId($location);

            $this->getRedisAdapter()->deleteItems([
                $cacheId,
                $cacheId . '_' . Ewe::getShortClassName(),
                $cacheId . '_' . Ram::getShortClassName(),
                $cacheId . '_' . Neuter::getShortClassName(),
                $historicCacheId,
                $historicCacheId . '_' . Ewe::getShortClassName(),
                $historicCacheId . '_' . Ram::getShortClassName(),
                $historicCacheId . '_' . Neuter::getShortClassName(),
                $candidateMotherId,
                $eweLivestockWithLastMateCacheId,
            ]);
        }
    }


    public function clear()
    {
        $this->getRedisAdapter()->clear();
    }


    /**
     * @param array $extraJmsGroups
     * @return string
     */
    public static function getJmsGroupsSuffix(array $extraJmsGroups = [])
    {
        return count($extraJmsGroups) > 0 ? implode('-', $extraJmsGroups) : '';
    }


    /**
     * @param array $filter is treated as an associative array
     * @return string
     */
    public static function getFilterSuffix(array $filter = [])
    {
        if (count($filter) === 0) {
            return '';
        }

        $implodedString = '';
        $prefix = '';
        foreach ($filter as $key => $value) {
            $implodedString .= $prefix . $key .'='.$value;
            $prefix = ',';
        }
        return $implodedString;
    }
}