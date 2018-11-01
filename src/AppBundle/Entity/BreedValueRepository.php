<?php

namespace AppBundle\Entity;
use AppBundle\Constant\BreedValueTypeConstant;
use AppBundle\Enumerator\DateTimeFormats;
use AppBundle\Setting\BreedGradingSetting;
use AppBundle\Util\ArrayUtil;
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

    /**
     * @param bool $useDateMonthYearFormat
     * @return null|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getBreedValueLastGenerationDate($useDateMonthYearFormat = true): ?string
    {
        $sql = "SELECT
                  generation_date
                FROM breed_value
                  INNER JOIN (
                               SELECT
                                 MAX(id) as max_id
                               FROM breed_value
                             )v ON v.max_id = breed_value.id";
        $result = $this->getConnection()->query($sql)->fetch();
        if (!$result) {
            return null;
        }

        $generationDateString =  ArrayUtil::get('generation_date', $result);
        if (!$generationDateString) {
            return null;
        }
        return $useDateMonthYearFormat ?
            (new \DateTime($generationDateString))->format(DateTimeFormats::DAY_MONTH_YEAR)
            : $generationDateString;
    }
}