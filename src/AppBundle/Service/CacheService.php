<?php

namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;
use Doctrine\ORM\Query;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Class CacheService
 */
class CacheService
{
    const CACHE_LIFETIME_IN_SECONDS = 3600;

    /** @var String */
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
     * @param Query $query
     * @return mixed
     */
    public function get($cacheId, $query)
    {
        $queryCache = $this->getRedisAdapter()->getItem($cacheId);
        if(!$queryCache->isHit()) {
            $queryCache->set($query->getResult());
            $queryCache->expiresAfter(self::CACHE_LIFETIME_IN_SECONDS);
            $this->getRedisAdapter()->save($queryCache);
        }
        $queryCache = $this->getRedisAdapter()->getItem($cacheId);
        return $queryCache->get();
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
     * Clears the redis cache for the Livestock of a given location , to reflect changes of animals on Livestock.
     *
     * @param Location $location
     * @param Animal | Ewe | Ram | Neuter $animal
     */
    public function clearLivestockCacheForLocation(Location $location = null, $animal = null) {
        if(!$location) {
            /** @var Location $location */
            $location = $animal->getLocation();
        }

        if($location) {
            $cacheId = AnimalRepository::LIVESTOCK_CACHE_ID .$location->getId();
            $historicCacheId = AnimalRepository::HISTORIC_LIVESTOCK_CACHE_ID .$location->getId();
            $this->getRedisAdapter()->deleteItems([
                $cacheId,
                $cacheId . Ewe::getShortClassName(),
                $cacheId . Ram::getShortClassName(),
                $cacheId . Neuter::getShortClassName(),
                $historicCacheId,
                $historicCacheId . Ewe::getShortClassName(),
                $historicCacheId . Ram::getShortClassName(),
                $historicCacheId . Neuter::getShortClassName(),
            ]);
        }
    }
}