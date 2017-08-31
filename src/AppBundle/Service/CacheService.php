<?php

namespace AppBundle\Service;


use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Entity\Ewe;
use AppBundle\Entity\Location;
use AppBundle\Entity\Neuter;
use AppBundle\Entity\Ram;

/**
 * Class CacheService
 */
class CacheService
{

    /** @var \Predis\Client|\Redis */
    private $redis;


    /**
     * CacheService constructor.
     * @param \Predis\Client $redis
     */
    public function __construct(\Predis\Client $redis)
    {
        $this->redis = $redis;
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

            $lastIndex = 10;
            for($i = 0; $i <= $lastIndex; $i++) {
                $this->redis->del('[' .$cacheId .']['.$i.']');
            }
        }
    }
}