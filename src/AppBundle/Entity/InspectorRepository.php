<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\PersonType;

/**
 * Class InspectorRepository
 * @package AppBundle\Entity
 */
class InspectorRepository extends PersonRepository {


    /**
     * @param string $firstName
     * @param string $lastName
     * @param bool $isActive
     * @param string $emailAddress
     * @return bool
     */
    public function insertNewInspector($firstName, $lastName, $isActive = true, $emailAddress = '')
    {
        $type = PersonType::INSPECTOR;

        $isInsertSuccessFul = false;
        $id = parent::insertNewPersonParentTable($type, $firstName, $lastName, $isActive, $emailAddress);
        if(is_int($id) && $id != 0) {
            $sql = "INSERT INTO inspector (id, object_type) VALUES (currval('person_id_seq'),'".$type."')";
            $this->getConnection()->exec($sql);
            $isInsertSuccessFul = true;
        }
        return $isInsertSuccessFul;
    }
    
    
    public function fixMissingInspectorTableRecords()
    {
        $sql = "SELECT p.id FROM person p
                  LEFT JOIN inspector i ON p.id = i.id
                WHERE p.type = 'Inspector' AND i.id ISNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();

        if (count($results) == 0) {
            return;
        }

        foreach ($results as $result) {
            $id = $result['id'];

            $sql = "INSERT INTO inspector (id, object_type) VALUES (" . $id . ",'Inspector') ";
            $this->getConnection()->exec($sql);
        }
    }


    /**
     * @param $personId
     */
    public function deleteInspector($personId)
    {
        $sql = "DELETE FROM inspector WHERE id = ".$personId;
        $this->getConnection()->exec($sql);

        $sql = "DELETE FROM token WHERE owner_id = ".$personId;
        $this->getConnection()->exec($sql);
        
        $sql = "DELETE FROM person WHERE type = 'Inspector' AND id = ".$personId;
        $this->getConnection()->exec($sql);
    }
}