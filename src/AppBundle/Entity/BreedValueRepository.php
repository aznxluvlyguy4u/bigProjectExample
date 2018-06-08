<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Setting\BreedGradingSetting;
use AppBundle\Util\DateUtil;
use AppBundle\Util\SqlUtil;

/**
 * Class BreedValueRepository
 * @package AppBundle\Entity
 */
class BreedValueRepository extends BaseRepository
{

    /**
     * @param string $breedValueTypeConstant
     * @param string|\DateTime $generationDate
     * @param boolean $isIncludingOnlyAliveAnimals
     * @return array
     * @throws \Doctrine\DBAL\DBALException|\Exception
     */
    public function getReliableBreedValues($breedValueTypeConstant, $generationDate, $isIncludingOnlyAliveAnimals)
    {
        $generationDateString = $generationDate instanceof \DateTime ? $generationDate->format(SqlUtil::DATE_FORMAT) : $generationDate;
        $generationYear = DateUtil::getYearFromDateStringOrDateTime($generationDateString);

        if ($generationDate === null || $generationYear === null) {
            throw new \Exception('Invalid generationDate entered for getReliableBreedValues with type: '.$breedValueTypeConstant);
        }

        $yearOfBirth = $generationYear - BreedGradingSetting::GENETIC_BASE_YEAR_OFFSET;
        $animalIsAliveFilter = $isIncludingOnlyAliveAnimals ? 'AND a.is_alive = TRUE' : '';

        $sql = "SELECT
                  b.value
                FROM breed_value b
                  INNER JOIN breed_value_type t ON b.type_id = t.id
                  INNER JOIN animal a ON b.animal_id = a.id
                WHERE 
                    DATE_PART('year', a.date_of_birth) = ".$yearOfBirth." AND
                    t.nl = '".$breedValueTypeConstant."' AND
                    b.generation_date = '".$generationDateString."' AND
                    b.reliability >= t.min_reliability ".$animalIsAliveFilter;

        return SqlUtil::getSingleValueGroupedFloatsFromSqlResults(
            'value',
            $this->getConnection()->query($sql)->fetchAll(),
            false
        );
    }

}