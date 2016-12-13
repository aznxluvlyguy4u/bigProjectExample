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
     * @param bool $hasSpaceBetweenCountryCodeAndNumber
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNewReplacementUlnSearchArray($hasSpaceBetweenCountryCodeAndNumber = false)
    {
        $spacing = $hasSpaceBetweenCountryCodeAndNumber ? ' ' : '';
        
        $sql = "SELECT uln_country_code_to_replace, uln_number_to_replace FROM declare_tag_replace
                INNER JOIN declare_base ON declare_tag_replace.id = declare_base.id
                WHERE request_state = 'FINISHED' OR request_state = 'FINISHED_WITH_WARNING'";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $finalReplacementUlnsByOldUlns = [];
        foreach ($results as $result) {
            $ulnToReplace = $result['uln_country_code_to_replace'].$spacing.$result['uln_number_to_replace'];
            $newestUln = $this->getNewReplacementUln($ulnToReplace);

            $ulnParts = Utils::getUlnFromString($newestUln, $hasSpaceBetweenCountryCodeAndNumber);
            $finalReplacementUlnsByOldUlns[$ulnToReplace] = $ulnParts;
        }
        
        return $finalReplacementUlnsByOldUlns;
    }
    

    /**
     * @param string $oldReplacedUln
     * @param boolean $hasSpaceBetweenCountryCodeAndNumber
     * @param boolean $isFirstIteration
     * @return string
     */
    public function getNewReplacementUln($oldReplacedUln, $hasSpaceBetweenCountryCodeAndNumber = false, $isFirstIteration = true)
    {
        $ulnParts = Utils::getUlnFromString($oldReplacedUln, $hasSpaceBetweenCountryCodeAndNumber);        
        if($ulnParts == null) { return null; }

        $oldReplacedUlnCountryCode = $ulnParts[Constant::ULN_COUNTRY_CODE_NAMESPACE];
        $oldReplacedUlnNumber = $ulnParts[Constant::ULN_NUMBER_NAMESPACE];

        $sql = "SELECT uln_country_code_replacement, uln_number_replacement, replace_date
            FROM declare_tag_replace d
            INNER JOIN declare_base b ON d.id = b.id
            WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') 
              AND d.uln_country_code_to_replace = '".$oldReplacedUlnCountryCode."' AND uln_number_to_replace = '".$oldReplacedUlnNumber."'
            ORDER BY replace_date DESC";
        $result = $this->getManager()->getConnection()->query($sql)->fetch();

        $spacing = $hasSpaceBetweenCountryCodeAndNumber ? ' ' : '';
        $foundNewReplacementUln = $result['uln_country_code_replacement'].$spacing.$result['uln_number_replacement'];

        if($isFirstIteration && $foundNewReplacementUln == null) { return $oldReplacedUln; }

        //Loop until you find the newest version of the uln for the animal
        do {
            $foundNewerReplacementUln = $this->getNewReplacementUln($foundNewReplacementUln, $hasSpaceBetweenCountryCodeAndNumber, false);
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