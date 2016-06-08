<?php

namespace AppBundle\Entity;


/**
 * Class ClientMigrationRepository
 * @package AppBundle\Entity
 */
class ClientMigrationRepository extends BaseRepository {


    public function getMigrationDataOfClient(Client $client)
    {
        return $this->findOneBy(array("client" => $client));
    }
}
