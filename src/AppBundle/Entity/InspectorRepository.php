<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\PersonType;
use AppBundle\Util\StringUtil;

/**
 * Class InspectorRepository
 * @package AppBundle\Entity
 */
class InspectorRepository extends PersonRepository {


    /**
     * @param string $firstName
     * @param string $lastName
     * @param bool $isActive
     * @return bool
     */
    public function insertNewInspector($firstName, $lastName, $isActive = true)
    {
        $type = PersonType::INSPECTOR;
        
        $isInsertSuccessFul = false;
        $isInsertParentSuccessFul = parent::insertNewPersonParentTable($type, $firstName, $lastName, $isActive);
        if($isInsertParentSuccessFul) {
            $sql = "INSERT INTO inspector (id, object_type) VALUES (currval('person_id_seq'),'".$type."')";
            $this->getManager()->getConnection()->exec($sql);
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
        
        if(count($results) == 0) { return; }
        
        foreach ($results as $result) {
            $id = $result['id'];
            
            $sql = "INSERT INTO inspector (id, object_type) VALUES (".$id.",'Inspector') ";
            $this->getConnection()->exec($sql);
        }
    }
}