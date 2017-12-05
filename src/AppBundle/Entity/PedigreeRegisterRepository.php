<?php

namespace AppBundle\Entity;

/**
 * Class PedigreeRegisterRepository
 * @package AppBundle\Entity
 */
class PedigreeRegisterRepository extends BaseRepository {

    /**
     * @param int $animalId
     * @param boolean $onlyIncludeIfRegisteredWithNsfo TODO
     * @return string
     */
    public function getFullnameByAnimalId($animalId, $onlyIncludeIfRegisteredWithNsfo = true)
    {
        if(!is_int($animalId)) { return null; }

        $filter = ' ';
        if($onlyIncludeIfRegisteredWithNsfo) {
            $filter = ' AND pedigree_register.is_registered_with_nsfo = TRUE ';
        }

        $sql = "SELECT pedigree_register.full_name FROM animal
                  INNER JOIN pedigree_register ON animal.pedigree_register_id = pedigree_register.id 
                WHERE animal.id = ".$animalId.' '.$filter;
        $result = $this->getConnection()->query($sql)->fetch();
        
        return $result == false ? null : $result['full_name'];
    }


    /**
     * @return array
     */
    public function getNsfoRegisters()
    {
        return $this->findBy(['isRegisteredWithNsfo' => true], ['abbreviation' => 'ASC']);
    }
    
}