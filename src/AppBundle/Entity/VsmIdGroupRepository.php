<?php

namespace AppBundle\Entity;

class VsmIdGroupRepository extends BaseRepository {

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getPrimaryVsmIdsBySecondaryVsmId()
    {
        $sql = "SELECT primary_vsm_id, secondary_vsm_id FROM vsm_id_group";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['secondary_vsm_id']] = $result['primary_vsm_id'];
        }

        return $searchArray;
    }

}
