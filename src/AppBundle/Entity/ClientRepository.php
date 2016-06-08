<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Setting\MigrationSetting;

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

    public function getByEmail($email)
    {
        $repository = $this->getEntityManager()->getRepository(Constant::CLIENT_REPOSITORY);
        $client = $repository->findOneBy(array("emailAddress" => $email));

        return $client;
    }

    /**
     * Get all clients that do not have a password yet.
     *
     * @return array
     */
    public function getClientsWithoutAPassword()
    {
        return $this->findBy(array("password" => MigrationSetting::EMPTY_PASSWORD_INDICATOR));
    }
}
