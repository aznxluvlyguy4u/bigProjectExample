<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\model\measurements\ExteriorData;
use AppBundle\Util\TimeUtil;

/**
 * Class ExteriorRepository
 * @package AppBundle\Entity
 */
class ExteriorRepository extends MeasurementRepository {

    const FILE_NAME = 'exterior_deleted_duplicates';
    const FILE_EXTENSION = '.txt';
    const FILE_NAME_TIME_STAMP_FORMAT = 'Y-m-d_H';

    const DELETE_FROM_EXTERIOR_WHERE_ID = "DELETE FROM exterior WHERE id = ";

    /** @var boolean */
    private $isPrintDeletedExteriors;

    /** @var string */
    private $mutationsFolder;


    /**
     * @param Animal $animal
     * @param string $nullFiller
     * @param bool $ignoreDeleted
     * @return array
     */
    public function getAllOfAnimalBySql(Animal $animal, $nullFiller = '', $ignoreDeleted = true)
    {
        $results = [];
        //null check
        if(!($animal instanceof Animal || !is_int($animal->getId()))) { return $results; }

        $deletedFilterString = '';
        if($ignoreDeleted) { $deletedFilterString = ' AND m.is_active = TRUE '; }

        $sql = "SELECT m.id as id, measurement_date, x.*, p.person_id, p.first_name, p.last_name
                FROM measurement m
                  INNER JOIN exterior x ON x.id = m.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                  INNER JOIN animal a ON a.id = x.animal_id
                WHERE x.animal_id = ".$animal->getId().$deletedFilterString." ORDER BY measurement_date DESC";
        $retrievedMeasurementData = $this->getConnection()->query($sql)->fetchAll();

        $count = 0;
        foreach ($retrievedMeasurementData as $measurementData)
        {
            $results[$count] = [
                JsonInputConstant::ID => $measurementData[JsonInputConstant::ID],
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue(JsonInputConstant::MEASUREMENT_DATE, $measurementData, $nullFiller),
                JsonInputConstant::HEIGHT => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::HEIGHT]),
                JsonInputConstant::KIND => Utils::fillNullOrEmptyString($measurementData[JsonInputConstant::KIND], $nullFiller),
                JsonInputConstant::PROGRESS => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::PROGRESS]),
                JsonInputConstant::SKULL => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::SKULL]),
                JsonInputConstant::MUSCULARITY => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::MUSCULARITY]),
                JsonInputConstant::PROPORTION => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::PROPORTION]),
                JsonInputConstant::EXTERIOR_TYPE => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::EXTERIOR_TYPE]),
                JsonInputConstant::LEG_WORK => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::LEG_WORK]),
                JsonInputConstant::FUR => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::FUR]),
                JsonInputConstant::GENERAL_APPEARANCE => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::GENERAL_APPEARANCE]),
                JsonInputConstant::BREAST_DEPTH => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::BREAST_DEPTH]),
                JsonInputConstant::TORSO_LENGTH => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::TORSO_LENGTH]),
                JsonInputConstant::MARKINGS => Utils::fillNullOfFloatValue($measurementData[JsonInputConstant::MARKINGS]),
            ];

            //Only include inspector key if it exists
            $personId = $measurementData[JsonInputConstant::PERSON_ID];
            if($personId != null && $personId != '') {
                $results[$count][JsonInputConstant::INSPECTOR] = [
                    JsonInputConstant::PERSON_ID => $personId,
                    JsonInputConstant::FIRST_NAME => Utils::fillNullOrEmptyString($measurementData[JsonInputConstant::FIRST_NAME], $nullFiller),
                    JsonInputConstant::LAST_NAME => Utils::fillNullOrEmptyString($measurementData[JsonInputConstant::LAST_NAME], $nullFiller),
                    JsonInputConstant::TYPE => "Inspector",
                ];
            }

            $count++;
        }
        return $results;
    }


    /**
     * NOTE! general_appearance is returned spelling corrected as general_appearance
     *
     * @param int $animalId
     * @param string $replacementString
     * @return array
     */
    public function getLatestExteriorBySql($animalId = null, $replacementString = null)
    {
        $nullResult = [
          JsonInputConstant::ID => $replacementString,
          JsonInputConstant::ANIMAL_ID => $replacementString,
          JsonInputConstant::SKULL => $replacementString,
          JsonInputConstant::MUSCULARITY => $replacementString,
          JsonInputConstant::PROPORTION => $replacementString,
          JsonInputConstant::EXTERIOR_TYPE => $replacementString,
          JsonInputConstant::LEG_WORK => $replacementString,
          JsonInputConstant::FUR => $replacementString,
          JsonInputConstant::GENERAL_APPEARANCE => $replacementString,
          JsonInputConstant::HEIGHT => $replacementString,
          JsonInputConstant::BREAST_DEPTH => $replacementString,
          JsonInputConstant::TORSO_LENGTH => $replacementString,
          JsonInputConstant::MARKINGS => $replacementString,
          JsonInputConstant::KIND => $replacementString,
          JsonInputConstant::PROGRESS => $replacementString,
          JsonInputConstant::MEASUREMENT_DATE => $replacementString,
        ];

        if(!is_int($animalId)) { return $nullResult; }

        $sqlBase = "SELECT x.id, x.animal_id, x.skull, x.muscularity, x.proportion, x.exterior_type, x.leg_work,
                      x.fur, x.general_appearance as general_appearance, x.height, x.breast_depth, x.torso_length, x.markings, x.kind, x.progress, m.measurement_date
                    FROM exterior x
                      INNER JOIN measurement m ON x.id = m.id
                      INNER JOIN (
                                   SELECT animal_id, max(m.measurement_date) as measurement_date
                                   FROM exterior e
                                     INNER JOIN measurement m ON m.id = e.id
                                   WHERE m.is_active = TRUE
                                   GROUP BY animal_id) y on y.animal_id = x.animal_id 
                    WHERE m.measurement_date = y.measurement_date AND m.is_active = TRUE ";

        if(is_int($animalId)) {
            $filter = "AND x.animal_id = " . $animalId;
            $sql = $sqlBase.$filter;
            $result = $this->getConnection()->query($sql)->fetch();
        } else {
            $filter = "";
            $sql = $sqlBase.$filter;
            $result = $this->getConnection()->query($sql)->fetchAll();
        }
        return is_bool($result) && !$result ? $nullResult : $result;
    }


    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContradictingExteriors()
    {
        $sql = "SELECT 
            n.id as id, a.id as animal_id, n.animal_id_and_date, n.measurement_date, n.log_date, n.inspector_id,
            z.*
         FROM measurement n
                  INNER JOIN (
                               SELECT m.animal_id_and_date
                               FROM measurement m
                                 INNER JOIN exterior x ON m.id = x.id
                               WHERE m.is_active
                               GROUP BY m.animal_id_and_date
                               HAVING (COUNT(*) > 1)
                             ) t on t.animal_id_and_date = n.animal_id_and_date
                  INNER JOIN exterior z ON z.id = n.id
                  LEFT JOIN person i ON i.id = n.inspector_id
                  LEFT JOIN animal a ON a.id = z.animal_id
                  WHERE n.is_active";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $resultsAsDataObject = array_map(function ($exteriorAsArray) {
            return new ExteriorData($exteriorAsArray);
        }, $results);

        return $this->groupSqlMeasurementObjectResultsByAnimalIdAndDate($resultsAsDataObject);
    }
}
