<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\Constant;

/**
 * Class DeclareTagReplaceRepository
 * @package AppBundle\Entity
 */
class DeclareTagReplaceRepository extends BaseRepository {

    /**
     * @param string $oldReplacedUln
     * @return string
     */
    public function getNewReplacementUln($oldReplacedUln)
    {
        $ulnParts = Utils::getUlnFromString($oldReplacedUln);
        if($ulnParts == null) { return null; }

        $oldReplacedUlnCountryCode = $ulnParts[Constant::ULN_COUNTRY_CODE_NAMESPACE];
        $oldReplacedUlnNumber = $ulnParts[Constant::ULN_NUMBER_NAMESPACE];

        $sql = "SELECT CONCAT(uln_country_code_replacement, uln_number_replacement) as new_replacement_uln, replace_date
            FROM declare_tag_replace d
            INNER JOIN declare_base b ON d.id = b.id
            WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') 
              AND d.uln_country_code_to_replace = '".$oldReplacedUlnCountryCode."' AND uln_number_to_replace = '".$oldReplacedUlnNumber."'
            ORDER BY replace_date DESC";
        $result = $this->getManager()->getConnection()->query($sql)->fetch();

        $foundNewReplacementUln = $result['new_replacement_uln'];

        //Loop until you find the newest version of the uln for the animal
        do {
            $foundNewerReplacementUln = $this->getNewReplacementUln($foundNewReplacementUln);
            if($foundNewerReplacementUln == null) {
                return $foundNewReplacementUln;
            } else {
                $foundNewReplacementUln = $foundNewerReplacementUln;
            }
        }
        while($foundNewerReplacementUln != null);
    }


    /**
     * @return array
     */
    public function getAnimalIdsByUlns()
    {
        $sql = "SELECT animal_id, uln_country_code_replacement, uln_number_replacement, uln_country_code_to_replace, uln_number_to_replace
                    FROM declare_tag_replace t
                    INNER JOIN declare_base b ON b.id = t.id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING')";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();
        
        $searchArray = [];
        foreach ($results as $result) {
            $animalId = $result['animal_id'];
            $ulnNew = $result['uln_country_code_replacement'].$result['uln_number_replacement'];
            $ulnOld = $result['uln_country_code_to_replace'].$result['uln_number_to_replace'];
            $searchArray[$ulnNew] = $animalId;
            $searchArray[$ulnOld] = $animalId;
        }
        return $searchArray;
    }
    
}