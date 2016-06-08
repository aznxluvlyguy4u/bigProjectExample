<?php

namespace AppBundle\Entity;


/**
 * Class ClientMigrationDataRepository
 * @package AppBundle\Entity
 */
class ClientMigrationDataRepository extends BaseRepository {


    public function getMigrationDataOfClient(Client $client)
    {
        return $this->findOneBy(array("client" => $client));
    }
}
