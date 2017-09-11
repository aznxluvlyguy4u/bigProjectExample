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
            $this->getRedisAdapter()->save($queryCache);
        }
        $queryCache = $this->getRedisAdapter()->getItem($cacheId);
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
            $this->getRedisAdapter()->deleteItem($cacheId);
        }
    }
}