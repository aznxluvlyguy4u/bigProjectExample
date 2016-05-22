<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;

class ClientRepository extends BaseRepository {


    public function getByToken($token)
    {

        $em = $this->getEntityManager();
        $client = $em->getRepository(Constant::CLIENT_REPOSITORY)->findOneBy(array('accessToken' => $token));

        return $client;
    }

    public function getRelationNumberKeeper($token)
    {
        $client = $this->getByToken($token);

        return $client->getRelationNumberKeeper();
    }

    public function getByRelationNumberKeeper($relationNumberKeeper)
    {
        $repository = $this->getEntityManager()->getRepository(Constant::CLIENT_REPOSITORY);
        $client = $repository->findOneBy(array("relationNumberKeeper" => $relationNumberKeeper));

        return $client;
    }

    public function getByMessageObject($messageObject)
    {
        return $this->getByRelationNumberKeeper($messageObject->getRelationNumberKeeper());
    }
}
