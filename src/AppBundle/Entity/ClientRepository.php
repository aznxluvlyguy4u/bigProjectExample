<?php

namespace AppBundle\Entity;

use AppBundle\Constant\Constant;
use AppBundle\Setting\MigrationSetting;

class ClientRepository extends BaseRepository {

    public function getByRelationNumberKeeper($relationNumberKeeper)
    {
        $repository = $this->getManager()->getRepository(Constant::CLIENT_REPOSITORY);
        $client = $repository->findOneBy(array("relationNumberKeeper" => $relationNumberKeeper));

        return $client;
    }

    public function getByMessageObject($messageObject)
    {
        return $this->getByRelationNumberKeeper($messageObject->getRelationNumberKeeper());
    }

    public function getByEmail($email)
    {
        $repository = $this->getManager()->getRepository(Constant::CLIENT_REPOSITORY);
        $client = $repository->findOneBy(array("emailAddress" => $email));

        return $client;
    }

    public function getByUbn($ubn)
    {
        $repository = $this->getManager()->getRepository(Constant::LOCATION_REPOSITORY);
        $location = $repository->findOneBy(array("ubn" => $ubn));
        if($location == null) {
            return null;
        } else {
            $client = $location->getCompany()->getOwner();
        }

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
