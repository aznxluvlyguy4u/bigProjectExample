<?php

namespace AppBundle\Entity;

/**
 * Class DeclareTagReplaceRepository
 * @package AppBundle\Entity
 */
class DeclareTagReplaceRepository extends BaseRepository {
    
    /**
     * @param $oldReplacedUln
     */
    public function getNewReplacementUln($oldReplacedUln)
    {
        $sql = "SELECT uln_number_replacement, replace_date
            FROM declare_tag_replace d
            INNER JOIN declare_base b ON d.id = b.id
            WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND uln_number_to_replace = '".$oldReplacedUln."'
            ORDER BY replace_date DESC";
        $result = $this->getManager()->getConnection()->query($sql)->fetch();

        return $result['uln_number_replacement'];
    }
    
}