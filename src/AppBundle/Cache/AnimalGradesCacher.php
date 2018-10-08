<?php


namespace AppBundle\Cache;


use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

class AnimalGradesCacher
{
    /**
     * @param Connection $conn
     * @return int
     * @throws \Exception
     */
    public static function updateAllDutchBreedStatuses(Connection $conn){
        return self::updateDutchBreedStatus($conn, null);
    }


    /**
     * $animalIds == null: all values in animalCache are updated
     * $animalIds count == 0; nothing is updated
     * $animalIds count > 0: only given animalIds are updated
     *
     * @param Connection $conn
     * @param array $animalIds
     * @return int
     * @throws \Exception
     */
    public static function updateDutchBreedStatus(Connection $conn, $animalIds)
    {
        $updateCount = 0;

        $animalIdFilterString = "";
        if(is_array($animalIds)) {
            if(count($animalIds) == 0) {
                return $updateCount;
            }
            else {
                $animalIdFilterString = " AND
                        a.id IN (
                          implode(',',$animalIds)
                        ) ";
            }
        } elseif($animalIds != null) {
            return $updateCount;
        }

        $sql = "UPDATE animal_cache SET dutch_breed_status = v.new_dutch_breed_status
                FROM (
                  SELECT
                    c.id as cache_id,
                    a_breed_types.dutch_first_letter as new_dutch_breed_status
                  FROM animal a
                    INNER JOIN animal_cache c ON c.animal_id = a.id
                    LEFT JOIN (VALUES
                      ".SqlUtil::breedTypeFirstLetterOnlyTranslationValues()."
                              ) AS a_breed_types(english, dutch_first_letter) ON a.breed_type = a_breed_types.english
                  WHERE (
                          (c.dutch_breed_status ISNULL AND a_breed_types.dutch_first_letter NOTNULL) OR
                          c.dutch_breed_status <> a_breed_types.dutch_first_letter
                        ) $animalIdFilterString
                ) AS v(cache_id, new_dutch_breed_status) WHERE animal_cache.id = v.cache_id
                ";
        return SqlUtil::updateWithCount($conn, $sql);
    }
}