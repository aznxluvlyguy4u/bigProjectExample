<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;

/**
 * Class ProvinceRepository
 * @package AppBundle\Entity
 */
class ProvinceRepository extends BaseRepository {

    public function findDutchProvinces()
    {
        $nl = $this->getEntityManager()->getRepository(Constant::COUNTRY_REPOSITORY)->findBy(array('code' => "NL"));
        return $this->getEntityManager()->getRepository(Constant::PROVINCE_REPOSITORY)->findBy(array('country' => $nl));
    }

}