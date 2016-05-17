<?php

namespace AppBundle\Entity;
use AppBundle\Constant\Constant;
use AppBundle\Enumerator\RequestType;

/**
 * Class DeclareArrivalResponseRepository
 * @package AppBundle\Entity
 */
class DeclareArrivalResponseRepository extends BaseRepository {


    /**
     * @param Client $client
     * @param $messageNumber
     * @return DeclareArrivalResponse|null
     */
    public function getArrivalResponseByMessageNumber(Client $client, $messageNumber)
    {
        return $this->getEntityManager()->getRepository(Constant::DECLARE_ARRIVAL_RESPONSE_REPOSITORY)->findOneBy(array("messageNumber"=>$messageNumber));
    }

}