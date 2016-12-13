<?php

namespace AppBundle\Entity;

/**
 * Class PedigreeRegisterRepository
 * @package AppBundle\Entity
 */
class PedigreeRegisterRepository extends BaseRepository {

    /**
     * @param int $animalId
     * @return string
     */
    public function getFullnameByAnimalId($animalId)
    {
        if(!is_int($animalId)) { return null; }
        $sql = "SELECT pedigree_register.full_name FROM animal
                  INNER JOIN pedigree_register ON animal.pedigree_register_id = pedigree_register.id 
                WHERE animal.id = ".$animalId;
        $result = $this->getManager()->getConnection()->query($sql)->fetch();
        return $result == false ? null : $result['full_name'];
    }
    
}