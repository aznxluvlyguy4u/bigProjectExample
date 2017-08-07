<?php

namespace AppBundle\Util;


use AppBundle\Enumerator\GenderType;
use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

/**
 * Class GenderChangerForMigrationOnly
 * @package AppBundle\Migration
 */
class GenderChangerBySql
{

    /**
     * Change gender or animal. If animal is not Male of Female, it cannot be changed back to neuter.
     *
     * @param Connection $conn
     * @param int $animalId
     * @param string $newGender
     * @throws \Exception
     * @return boolean
     */
    public static function changeGender(Connection $conn, $animalId, $newGender) {

        if(!is_int($animalId)) {
            throw new \Exception('Incorrect animalId. It must be an integer');
        }

        if ($newGender !== GenderType::NEUTER && $newGender !== GenderType::MALE && $newGender !== GenderType::FEMALE) {
            throw new \Exception('Incorrect new gender value. Allowed values: FEMALE, MALE, NEUTER');
        }

        $oldGender = null;
        $isGenderChanged = false;

        try {
            $sql = "SELECT gender FROM animal WHERE id = ".$animalId;
            $result = $conn->query($sql)->fetch();
            if ($result === null) {
                throw new \Exception('No animal found for given animalId: '.$animalId);
            }

            $oldGender = $result['gender'];

            if ($oldGender === $newGender) {
                return true;
            }

            $oldTableName = strtolower($oldGender);
            //$oldType = ucfirst(strtolower($oldGender));
            $newTableName = strtolower($newGender);
            $newType = ucfirst(strtolower($newGender));

            $sql = "UPDATE animal SET type='$newType', gender = '$newGender' WHERE id = ". $animalId;
            $updateCount = SqlUtil::updateWithCount($conn, $sql);
            $isGenderChanged = $updateCount > 0;

            $sql = "SELECT id FROM $newTableName WHERE id = ". $animalId;
            $resultEwe = $conn->query($sql)->fetch();

            if($resultEwe['id'] == '' || $resultEwe['id'] == null) {
                $sql = "INSERT INTO $newTableName VALUES (" . $animalId . ", '$newType')";
                $conn->exec($sql);
            }

            $sql = "SELECT id FROM $oldTableName WHERE id = ". $animalId;
            $resultNeuter = $conn->query($sql)->fetch();

            if($resultNeuter['id'] != '' || $resultNeuter['id'] != null) {
                $sql = "DELETE FROM $oldTableName WHERE id = " . $animalId;
                $conn->exec($sql);
            }

        } catch (\Exception $exception) {
            throw $exception;

        } finally {

            if ($isGenderChanged) {
                $oldGender = ucfirst(strtolower($oldGender));
                $newGender = ucfirst(strtolower($newGender));

                $sql = "INSERT INTO gender_history_item (animal_id, log_date, previous_gender, new_gender)
                    VALUES ($animalId,NOW(),'$oldGender','$newGender')";
                $conn->exec($sql);
            }

            return $isGenderChanged;
        }

    }


}