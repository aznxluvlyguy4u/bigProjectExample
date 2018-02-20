<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Util\SqlUtil;

/**
 * Class BreedValueRepository
 * @package AppBundle\Entity
 */
class BreedValueRepository extends BaseRepository
{

    /**
     * @param string|int $yearOfBirth used as year of measurement
     * @param boolean $isIncludingOnlyAliveAnimals
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getReliableSIgAValues($yearOfBirth, $isIncludingOnlyAliveAnimals)
    {
        $animalIsAliveFilter = $isIncludingOnlyAliveAnimals ? 'AND a.is_alive = TRUE' : '';

        $sql = "SELECT
                  b.value
                FROM breed_value b
                  INNER JOIN breed_value_type t ON b.type_id = t.id
                  INNER JOIN animal a ON b.animal_id = a.id
                WHERE DATE_PART('year', a.date_of_birth) = ".$yearOfBirth." 
                    AND t.nl = '".BreedValueTypeConstant::IGA_SCOTLAND."'
                    AND b.reliability >= t.min_reliability ".$animalIsAliveFilter;
        return $this->getConnection()->query($sql)->fetchAll();
    }


    /**
     * @param string|\DateTime $generationDate
     * @param string $breedValueTypeNl
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMeasurementYearsOfGenerationSet($generationDate, $breedValueTypeNl)
    {
        $generationDateString = $generationDate instanceof \DateTime ? $generationDate->format(SqlUtil::DATE_FORMAT) : $generationDate;

        $sql = "SELECT
                  DATE_PART('year', a.date_of_birth) as year
                FROM breed_value b
                  INNER JOIN breed_value_type t ON b.type_id = t.id
                  INNER JOIN animal a ON b.animal_id = a.id
                WHERE b.generation_date = '".$generationDateString."' AND t.nl = '".$breedValueTypeNl."'
                      AND b.reliability >= t.min_reliability
                      AND a.date_of_birth NOTNULL
                GROUP BY DATE_PART('year', a.date_of_birth)";
        return $this->getConnection()->query($sql)->fetchAll();
    }

}