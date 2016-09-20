<?php

namespace AppBundle\Entity;

/**
 * Class LocationHealthRepository
 * @package AppBundle\Entity
 */
class LocationHealthRepository extends BaseRepository {

    /**
     * @param int $id
     * @return array
     */
    public function getAllAfterId($locationHealthId, $location)
    {
        //TODO MORE OPTIMIZATION NEEDED

        $locationId = $location->getId();

        $sql = "SELECT id FROM location_health
                WHERE (id > '"  . $locationHealthId . "' AND  location_id = '"  . $locationId . "')";

        $query = $this->getManager()->getConnection()->prepare($sql);
        $query->execute();

        $results = array();

        //FIXME HOW TO RETURN OBJECTS DIRECTLY WITHOUT NEEDING find(id)
        foreach($query->fetchAll() as $item) {
            $results[] = $this->find($item['id']);
        }

        return $results;
    }

}