<?php

namespace AppBundle\Entity;
use AppBundle\Component\Utils;
use AppBundle\Constant\JsonInputConstant;
use AppBundle\Constant\MeasurementConstant;
use AppBundle\model\measurements\BodyFatData;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\DBALException as DBALExceptionAlias;

/**
 * Class BodyFatRepository
 * @package AppBundle\Entity
 */
class BodyFatRepository extends MeasurementRepository
{


    /**
     * @param  Animal  $animal
     * @return array
     */
    public function getAllOfAnimalBySql(Animal $animal, $nullFiller = '')
    {
        $results = [];
        //null check
        if (!($animal instanceof Animal)) {
            return $results;
        } elseif (!is_int($animal->getId())) {
            return $results;
        }

        $sql = "SELECT m.id as id, measurement_date, fat1.fat as fat1 , fat2.fat as fat2 , fat3.fat as fat3,
                p.person_id, p.first_name, p.last_name
                FROM measurement m
                INNER JOIN body_fat bf ON bf.id = m.id
                  LEFT JOIN fat1 ON bf.fat1_id = fat1.id
                  LEFT JOIN fat2 ON bf.fat2_id = fat2.id
                  LEFT JOIN fat3 ON bf.fat3_id = fat3.id
                  LEFT JOIN person p ON p.id = m.inspector_id
                WHERE bf.animal_id = ".$animal->getId();
        $retrievedMeasurementData = $this->getManager()->getConnection()->query($sql)->fetchAll();

        foreach ($retrievedMeasurementData as $measurementData) {
            $results[] = [
                JsonInputConstant::MEASUREMENT_DATE => TimeUtil::getDateTimeFromNullCheckedArrayValue('measurement_date',
                    $measurementData, $nullFiller),
                JsonInputConstant::FAT1 => Utils::fillNullOrEmptyString($measurementData['fat1'], $nullFiller),
                JsonInputConstant::FAT2 => Utils::fillNullOrEmptyString($measurementData['fat2'], $nullFiller),
                JsonInputConstant::FAT3 => Utils::fillNullOrEmptyString($measurementData['fat3'], $nullFiller),
                JsonInputConstant::PERSON_ID => Utils::fillNullOrEmptyString($measurementData['person_id'],
                    $nullFiller),
                JsonInputConstant::FIRST_NAME => Utils::fillNullOrEmptyString($measurementData['first_name'],
                    $nullFiller),
                JsonInputConstant::LAST_NAME => Utils::fillNullOrEmptyString($measurementData['last_name'],
                    $nullFiller),
            ];
        }
        return $results;
    }


    /**
     * @param  Animal  $animal
     * @param  \DateTime  $dateTime
     * @return Collection|BodyFat[]
     */
    public function findByAnimalAndDate(Animal $animal, \DateTime $dateTime)
    {
        return $this->getManager()->getRepository(BodyFat::class)->matching(
            $this->findByAnimalAndDateCriteria($animal, $dateTime)
        );
    }


    /**
     * @param  Animal  $animal
     * @return array
     */
    public function getLatestBodyFat(Animal $animal)
    {
        $bodyFat = array();

        //Measurement Criteria
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('animal', $animal))
            ->orderBy(['measurementDate' => Criteria::DESC])
            ->setMaxResults(1);

        /**
         * @var BodyFat $latestBodyFat
         */
        $latestBodyFat = $this->getManager()->getRepository(BodyFat::class)
            ->matching($criteria);

        if (sizeof($latestBodyFat) > 0) {
            $latestBodyFat = $latestBodyFat->get(0);
            $measurementDate = $latestBodyFat->getMeasurementDate();
            $fatOne = $latestBodyFat->getFat1()->getFat();
            $fatTwo = $latestBodyFat->getFat2()->getFat();
            $fatThree = $latestBodyFat->getFat3()->getFat();

            $bodyFat[MeasurementConstant::DATE] = $measurementDate;
            $bodyFat[MeasurementConstant::ONE] = $fatOne;
            $bodyFat[MeasurementConstant::TWO] = $fatTwo;
            $bodyFat[MeasurementConstant::THREE] = $fatThree;
        } else {
            $bodyFat[MeasurementConstant::DATE] = '';
            $bodyFat[MeasurementConstant::ONE] = 0.00;
            $bodyFat[MeasurementConstant::TWO] = 0.00;
            $bodyFat[MeasurementConstant::THREE] = 0.00;
        }
        return $bodyFat;
    }


    /**
     * @return array
     * @throws DBALExceptionAlias
     */
    public function getContradictingBodyFats()
    {
        $sql = "SELECT n.id as id, a.id as animal_id, n.animal_id_and_date, n.measurement_date, n.log_date,
                        fat1.fat as fat1,  fat2.fat as fat2, fat3.fat as fat3, n.inspector_id
                  FROM measurement n
                  INNER JOIN (
                               SELECT m.animal_id_and_date
                               FROM measurement m
                                 INNER JOIN body_fat x ON m.id = x.id
                               WHERE m.is_active
                               GROUP BY m.animal_id_and_date
                               HAVING (COUNT(*) > 1)
                             ) t on t.animal_id_and_date = n.animal_id_and_date
                  INNER JOIN body_fat z ON z.id = n.id
                  INNER JOIN fat1 ON z.fat1_id = fat1.id
                  INNER JOIN fat2 ON z.fat2_id = fat2.id
                  INNER JOIN fat3 ON z.fat3_id = fat3.id
                  LEFT JOIN animal a ON a.id = z.animal_id
                  WHERE n.is_active";
        $results = $this->getManager()->getConnection()->query($sql)->fetchAll();

        $resultsAsDataObject = array_map(function ($bodyFatsInArray) {
            return new BodyFatData($bodyFatsInArray);
        }, $results);

        return $this->groupSqlMeasurementObjectResultsByAnimalIdAndDate($resultsAsDataObject);
    }

}
