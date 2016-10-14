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
}