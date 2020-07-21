<?php

namespace AppBundle\Entity;

use AppBundle\Util\Translation;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Class TreatmentRepository
 * @package AppBundle\Entity
 */
class TreatmentRepository extends BaseRepository {

    const TREATMENT_WHERE_CONDITIONS = "
            WHERE l.ubn = :ubn
            AND (
                LOWER(t.description) LIKE LOWER(:query) OR 
                a.animal_order_number LIKE LOWER(:query) OR 
                LOWER(tm.name) LIKE LOWER(:query) OR 
                a.collar_number LIKE LOWER(:query) OR 
                LOWER(a.collar_color) LIKE LOWER(:query) OR 
                CONCAT(LOWER(a.collar_color), a.collar_number) LIKE LOWER(:query) OR
                CONCAT(LOWER(a.collar_color), ' ', a.collar_number) LIKE LOWER(:query)
            )
        ";

    const TREATMENT_JOINS = "
            INNER JOIN location l ON t.location_id = l.id
            INNER JOIN treatment_animal ta ON ta.treatment_id = t.id
            INNER JOIN animal a ON a.id = ta.animal_id
            INNER JOIN treatment_template tt ON t.treatment_template_id = tt.id
            LEFT JOIN medication_selection ms ON ms.treatment_id = t.id
            LEFT JOIN treatment_medication tm ON tm.id = ms.treatment_medication_id
        ";

    public function getHistoricTreatmentsTotalCount($ubn, $searchQuery = '')
    {
        $searchQuery = "%$searchQuery%";

        $countSql = "
            SELECT DISTINCT 
                t.id
            FROM treatment t
            ".self::TREATMENT_JOINS."
            ".self::TREATMENT_WHERE_CONDITIONS."
        ";

        $countStatement = $this->getManager()->getConnection()->prepare($countSql);
        $countStatement->bindParam('ubn', $ubn);
        $countStatement->bindParam('query', $searchQuery);
        $countStatement->execute();

        return $countStatement->rowCount();
    }


    /**
     * @param $ubn
     * @param int $page
     * @param int $perPage
     * @param string $searchQuery
     * @return array
     * @throws DBALException
     */
    public function getHistoricTreatments($ubn, $page = 1, $perPage = 10, $searchQuery = '')
    {
        $searchQuery = "%$searchQuery%";

        $sql = "
            SELECT DISTINCT 
                t.id as treatment_id,
                t.create_date,
                t.description,
                t.start_date,
                t.end_date,
                t.revoke_date,
                t.type,
                tt.is_editable,
                t.status
            FROM treatment t
            ".self::TREATMENT_JOINS."
            ".self::TREATMENT_WHERE_CONDITIONS."
            ORDER BY t.create_date DESC
            OFFSET ".$perPage." * (".$page." - 1)
            FETCH NEXT ".$perPage." ROWS ONLY
        ";

        $statement = $this->getManager()->getConnection()->prepare($sql);
        $statement->bindParam('ubn', $ubn);
        $statement->bindParam('query', $searchQuery);
        $statement->execute();

        $treatmentDetails = $statement->fetchAll();

        // First retrieve all the details, to minimize the database queries

        $treatmentIds = array_map(function (array $item) {
            return $item['treatment_id'];
        }, $treatmentDetails);

        $medicationDetails = $this->getMedicationDetails($treatmentIds);
        $treatmentAnimalDetailsSet = $this->getTreatmentAnimalDetails($treatmentIds);

        $animalIds = array_map(function (array $item) {
            return $item['animal_id'];
        }, $treatmentAnimalDetailsSet);

        $flagDetails = $this->getEntityManager()->getRepository(DeclareAnimalFlag::class)->getLatestFlagDetails($animalIds);

        // Then group and map the data in the correct output format

        foreach ($treatmentDetails as $treatmentKey => $item) {
            $treatmentId = $item['treatment_id'];

            $treatmentDetails[$treatmentKey]['dutchType'] = Translation::getDutchTreatmentType($item['type']);

            $treatmentDetails[$treatmentKey]['medications'] = array_values(array_filter(
                $medicationDetails,
                function (array $medication) use ($treatmentId) {
                    return $medication['treatment_id'] === $treatmentId;
                }));

            $animalDetailsOfTreatment = array_values(array_filter(
                $treatmentAnimalDetailsSet,
                function (array $treatmentAnimalDetailsItem) use ($treatmentId) {
                    return $treatmentAnimalDetailsItem['treatment_id'] === $treatmentId;
                }));

            foreach ( $animalDetailsOfTreatment as $animalKey => $animalDetailOfTreatment) {
                $animalIdOfTreatment = $animalDetailOfTreatment['animal_id'];
                $flagDetailsOfAnimalWrappedInArray = array_map(
                    function (array $filteredAnimalDetails) {
                        return [
                            'rvo_flag' => $filteredAnimalDetails['flag_type'],
                            'rvo_flag_status' => $filteredAnimalDetails['request_state'],
                            'rvo_flag_start_date' => $filteredAnimalDetails['start_date_in_default_format'],
                            'rvo_flag_end_date' => $filteredAnimalDetails['end_date_in_default_format'],
                        ];
                    },
                    array_filter($flagDetails,
                    function (array $flag) use ($animalIdOfTreatment) {
                        return $flag['animal_id'] === $animalIdOfTreatment;
                    }
                ));

                $flagDetailsOfAnimal = array_shift($flagDetailsOfAnimalWrappedInArray);

                if (is_array($flagDetailsOfAnimal) && !empty($flagDetailsOfAnimal)) {
                    $mergedAnimalDetails = array_merge($animalDetailOfTreatment, $flagDetailsOfAnimal);
                } else {
                    $mergedAnimalDetails = $animalDetailOfTreatment;
                }

                $treatmentDetails[$treatmentKey]['animals'][] = $mergedAnimalDetails;
            }
        }

        return $treatmentDetails;
    }


    private function getMedicationDetails(array $treatmentIds): array
    {
        $sql = 'SELECT
                    ms.treatment_id,
                    tm.name,
                    ms.waiting_time_end,
                    tm.dosage,
                    tm.dosage_unit,
                    tm.reg_nl,
                    tm.treatment_duration
               FROM medication_selection ms 
               INNER JOIN treatment_medication tm ON ms.treatment_medication_id = tm.id
               WHERE ms.treatment_id IN (?)';

        $values = [$treatmentIds];
        $types = [Connection::PARAM_INT_ARRAY];

        $statement = $this->getManager()->getConnection()
            ->executeQuery($sql, $values, $types);
        return $statement->fetchAll();
    }


    private function getTreatmentAnimalDetails(array $treatmentIds): array
    {
        $sql = 'SELECT 
                    t.treatment_id,
                    t.animal_id,
                    a.uln_country_code,
                    a.uln_number,
                    a.collar_color,
                    a.collar_number,
                    a.gender,
                    a.date_of_birth
                FROM treatment_animal t
                    INNER JOIN animal a ON a.id = t.animal_id
                WHERE t.treatment_id IN (?)';

        $values = [$treatmentIds];
        $types = [Connection::PARAM_INT_ARRAY];

        $statement = $this->getManager()->getConnection()
            ->executeQuery($sql, $values, $types);
        return $statement->fetchAll();
    }


    /**
     * @param Location $location
     * @return mixed
     */
    public function getLastTreatmentOfLocation(Location $location)
    {
        return $this->createQueryBuilder('treatment')
            ->innerJoin('treatment.location', 'location')
            ->where('location = :location')
            ->setParameter('location', $location)
            ->orderBy('treatment.createDate', 'DESC')
            ->getQuery()
            ->getResult()[0];
    }
}
