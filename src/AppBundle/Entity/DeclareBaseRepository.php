<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StoredProcedure;
use AppBundle\Util\TimeUtil;
use AppBundle\Util\Validator;

/**
 * Class DeclareBaseRepository
 * @package AppBundle\Entity
 */
class DeclareBaseRepository extends BaseRepository
{
    /**
     * @param Animal $animal
     * @param Location $location
     * @param string $replacementString
     * @return array
     */
    public function getLog(Animal $animal, $location, $replacementString = '')
    {
        $results = [];
        //null check
        if(!($animal instanceof Animal) || !($location instanceof Location)) { return $results; }
        elseif(!is_int($animal->getId())){ return $results; }

        if($animal->getLocation() != $location) { return $results; }
        
        $locationId = $location->getId();
        $animalId = $animal->getId();
        
        $sql = "SELECT b.log_date, start_date, end_date, 'MATE' as action, NULL as data, p.first_name, p.last_name
                FROM mate d
                  INNER JOIN declare_nsfo_base b ON d.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND (d.stud_ewe_id = ".$animalId." OR d.stud_ram_id = ".$animalId.")
                UNION
                SELECT b.log_date, d.date_of_birth as start_date, null as end_date, 'BIRTH' as action, NULL as data, p.first_name, p.last_name
                FROM declare_birth d
                  INNER JOIN declare_base b ON d.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                  LEFT JOIN litter l ON l.id = d.litter_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND (l.animal_father_id = ".$animalId." OR l.animal_mother_id = ".$animalId.")
                UNION
                SELECT b.log_date, measurement_date as start_date, NULL as end_date, 'WEIGHT MEASUREMENT KG' as action, CAST(d.weight AS TEXT) as data, p.first_name, p.last_name
                FROM declare_weight d
                  INNER JOIN declare_nsfo_base b ON d.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND d.animal_id = ".$animalId."
                UNION
                SELECT b.log_date, a.arrival_date as start_date, NULL as end_date, 'ARRIVAL FROM UBN' as action, a.ubn_previous_owner as data, p.first_name, p.last_name
                FROM declare_arrival a
                  INNER JOIN declare_base b ON a.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND a.animal_id = ".$animalId."
                UNION
                SELECT b.log_date, a.import_date as start_date, NULL as end_date, 'IMPORT FROM' as action, a.animal_country_origin as data, p.first_name, p.last_name
                FROM declare_import a
                  INNER JOIN declare_base b ON a.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND a.animal_id = ".$animalId."
                UNION
                SELECT b.log_date, a.depart_date as start_date, NULL as end_date, 'DEPARTURE TO UBN' as action, a.ubn_new_owner as data, p.first_name, p.last_name
                FROM declare_depart a
                  INNER JOIN declare_base b ON a.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND a.animal_id = ".$animalId."
                UNION
                SELECT b.log_date, a.export_date as start_date, NULL as end_date, 'EXPORT' as action, NULL as data, p.first_name, p.last_name
                FROM declare_export a
                  INNER JOIN declare_base b ON a.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND a.animal_id = ".$animalId."
                UNION
                SELECT b.log_date, a.date_of_death as start_date, NULL as end_date, 'LOSS' as action, a.reason_of_loss as data, p.first_name, p.last_name
                FROM declare_loss a
                  INNER JOIN declare_base b ON a.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND a.animal_id = ".$animalId."
                UNION
                SELECT b.log_date, a.replace_date as start_date, NULL as end_date, 'TAG REPLACE REPLACED ULN' as action, CONCAT(a.uln_country_code_to_replace,a.uln_number_to_replace) as data, p.first_name, p.last_name
                FROM declare_tag_replace a
                  INNER JOIN declare_base b ON a.id = b.id
                  LEFT JOIN person p ON p.id = b.action_by_id
                WHERE (b.request_state = 'FINISHED' OR b.request_state = 'FINISHED_WITH_WARNING') AND location_id = ".$locationId." AND a.animal_id = ".$animalId."
                ORDER BY log_date DESC";
        
        $retrievedData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedData as $record) {
            $results[] = [
              JsonInputConstant::LOG_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('log_date', $record, $replacementString),
              JsonInputConstant::START_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('start_date', $record, $replacementString),
              JsonInputConstant::END_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('end_date', $record, $replacementString),
              JsonInputConstant::ACTION => Utils::fillNullOrEmptyString($record['action'], $replacementString),
              JsonInputConstant::DATA => Utils::fillNullOrEmptyString($record['data'], $replacementString),
              JsonInputConstant::FIRST_NAME => Utils::fillNullOrEmptyString($record['first_name'], $replacementString),
              JsonInputConstant::LAST_NAME => Utils::fillNullOrEmptyString($record['last_name'], $replacementString),
            ];
        }

        return $results;
    }


    /**
     * @param bool $showHiddenForAdmin
     * @return array
     */
    public function getErrorsOverview($showHiddenForAdmin = false)
    {
        return StoredProcedure::getErrorMessages($this->getConnection(), $showHiddenForAdmin);
    }


    public function getErrorDetails($messageId)
    {
        /** @var DeclareBase $declare */
        $declare = $this->findOneByRequestId($messageId);

        if ($declare === null) {
            return Validator::createJsonResponse('No declare found for given messageId: '.$messageId, 428);
        }

        if ($declare->getRequestState() !== RequestStateType::FAILED) {
            return Validator::createJsonResponse('Declare does NOT have FAILED requestState, but: '.$declare->getRequestState(), 428);
        }

        if (
            $declare instanceof DeclareArrival ||
            $declare instanceof DeclareImport ||
            $declare instanceof DeclareDepart ||
            $declare instanceof DeclareExport ||
            $declare instanceof DeclareLoss ||
            $declare instanceof DeclareTagReplace ||
            $declare instanceof DeclareTagsTransfer ||
            $declare instanceof RevokeDeclaration
        ) {
            return $declare;

        } elseif ($declare instanceof DeclareBirth) {
            //TODO return the entire litter
            return $declare;

        }
    }

}