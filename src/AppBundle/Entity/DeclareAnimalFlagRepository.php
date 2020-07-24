<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

/**
 * Class DeclareAnimalFlagRepository
 * @package AppBundle\Entity
 */
class DeclareAnimalFlagRepository extends BaseRepository
{

    /**
     * @param array $treatmentIds
     * @return array
     */

    public function getFlagDetailsByTreatmentIds(array $treatmentIds): array
    {
        if (empty($treatmentIds)) {
            return [];
        }

        $dateFormat = SqlUtil::TO_CHAR_DATE_FORMAT;
        $sql = "SELECT
                    flag.animal_id,
                    flag.location_id,
                    flag.id as flag_id,
                    flag.flag_type,
                    flag.flag_start_date,
                    flag.flag_end_date,
                    to_char(flag_start_date, '$dateFormat') as start_date_in_default_format,
                    to_char(flag_end_date, '$dateFormat') as end_date_in_default_format,
                    db.request_state
                FROM declare_animal_flag flag
                    INNER JOIN declare_base db on flag.id = db.id
                WHERE treatment_id IN (?)";
        $values = [$treatmentIds];
        $types = [Connection::PARAM_INT_ARRAY];

        $statement = $this->getManager()->getConnection()->executeQuery($sql, $values, $types);
        return $statement->fetchAll();
    }


    /**
     * @param array $animalIds
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLatestFlagDetails(array $animalIds): array
    {
        if (empty($animalIds)) {
            return [];
        }

        $dateFormat = SqlUtil::TO_CHAR_DATE_FORMAT;
        $sql = "SELECT
                    flag.animal_id,
                    flag.location_id,
                    flag.id as flag_id,
                    flag.flag_type,
                    flag.flag_start_date,
                    flag.flag_end_date,
                    to_char(flag_start_date, '$dateFormat') as start_date_in_default_format,
                    to_char(flag_end_date, '$dateFormat') as end_date_in_default_format,
                    db.request_state
                FROM declare_animal_flag flag
                    INNER JOIN declare_base db on flag.id = db.id
                    INNER JOIN (
                        SELECT
                            animal_id,
                            MAX(last_flag_ordinal) as last_flag_id
                        FROM (
                            SELECT
                            animal_id,
                            flag_id,
                            flag_ordinal,
                            first_value(flag_id)
                                over (
                                    partition by animal_id
                                    order by flag_ordinal DESC
                                    range between unbounded preceding and unbounded following
                                ) as last_flag_ordinal
                        FROM (
                            -- Return the flags marked by star_date order grouped by animal_id
                            SELECT flag.animal_id, flag.id as flag_id,
                                DENSE_RANK() OVER (PARTITION BY flag.animal_id ORDER BY flag.flag_start_date) AS flag_ordinal
                              FROM declare_animal_flag flag
                                INNER JOIN declare_base db ON db.id = flag.id
                            WHERE --db.request_state IN ('FINISHED','FINISHED_WITH_WARNING') AND
                                  flag.animal_id IN (?)
                        ORDER BY animal_id, flag.id
                        )litter_ordinals
                            )last_litter_ids
                        GROUP BY animal_id
                    )last_flag_ids ON last_flag_ids.animal_id = flag.animal_id AND last_flag_ids.last_flag_id = flag.id";
        $values = [$animalIds];
        $types = [Connection::PARAM_INT_ARRAY];

        $statement = $this->getManager()->getConnection()->executeQuery($sql, $values, $types);
        return $statement->fetchAll();
    }
}
