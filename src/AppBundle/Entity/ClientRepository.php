<?php

namespace AppBundle\Entity;

class ClientRepository extends BaseRepository {


    public function getByToken($token)
    {

        $em = $this->getEntityManager();
        $client = $em->getRepository('AppBundle:Client')->findOneBy(array('accessToken' => $token));

        return $client;
    }

    public function getRelationNumberKeeper($token)
    {
        $client = $this->getByToken($token);

        return $client->getRelationNumberKeeper();
    }
}
