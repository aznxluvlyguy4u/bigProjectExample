<?php

namespace AppBundle\Service\Migration;

use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\TimeUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class AnimalTableMigrator
 *
 * Migrating the data from the animal_migration_table to animal table.
 */
class AnimalTableMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    const BATCH_SIZE = 10000;

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir, self::BATCH_SIZE);
    }


    /** @inheritdoc */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->preparationFixes();
        $this->fixGenderOfNeutersByMigrationValues();
        $this->migrateNewAnimalsJoinedOnUlnAndDateOfBirth();
        $this->migrateNewAnimals();
        $this->updateIncongruentParentIdsInAnimalMigrationTable();
        $this->updateParentIdsInAnimalTable();
    }


    public function preparationFixes()
    {
        $queries = [
            'Fill missing date_of_births in animal table ...' =>
              "UPDATE animal SET date_of_birth = v.date_of_birth
                FROM(
                      SELECT a.id as animal_id, amt.date_of_birth
                      FROM animal_migration_table amt
                        INNER JOIN animal a ON a.uln_number = amt.uln_number
                      WHERE a.name ISNULL AND (a.date_of_birth ISNULL AND amt.date_of_birth NOTNULL)
                ) AS v(animal_id, date_of_birth) WHERE animal.id = v.animal_id",
        ];

        foreach ($queries as $title => $query) {
            $this->updateBySql($title, $query);
        }
    }


    public function migrateNewAnimalsJoinedOnUlnAndDateOfBirth()
    {
        /* Query to check the data manually
        $sql = "SELECT amt.vsm_id, a.id as animal_id,
                  a.gender as gender_old, amt.gender_in_file as gender_new,
                  a.nickname as nickname_old, amt.nickname as nickname_new,
                  a.pedigree_number as pedigree_number_old, amt.pedigree_country_code, amt.pedigree_number as pedigree_number_new,
                  a.date_of_birth as date_of_birth_old, amt.date_of_birth as date_of_birth_new,
                  a.breed_code as breed_code_old, amt.breed_code as breed_code_new,
                  a.breed_type as breed_type_old, amt.breed_type as breed_type_new,
                  a.ubn_of_birth as ubn_of_birth_old, amt.ubn_of_birth as ubn_of_birth_new,
                  a.location_id as location_id_old, amt.location_of_birth_id as location_of_birth_new,
                  a.pedigree_register_id as pedigree_register_old, amt.pedigree_register_id as pedigree_register_id_new,
                  a.scrapie_genotype as scrapie_genotype_old, amt.scrapie_genotype as scrapie_genotype_new
                FROM animal_migration_table amt
                  INNER JOIN animal a ON a.uln_number = amt.uln_number AND a.date_of_birth = amt.date_of_birth
                WHERE a.name ISNULL";
        */

        //0. Check if any updates are actually necessary
        $sql = "SELECT COUNT(*) as count
                FROM animal_migration_table amt
                  INNER JOIN animal a ON a.uln_number = amt.uln_number AND a.date_of_birth = amt.date_of_birth
                WHERE a.name ISNULL";
        $toUpdateCount = $this->conn->query($sql)->fetch()['count'];
        if ($toUpdateCount === 0) {
            return;
        }

        //1. First update values

        $this->writeLn('=== Fill simple values in animal table by values in animal_migration_table ===');

        $columnVars = ['nickname', 'pedigree_country_code', 'pedigree_number', 'breed_code', 'breed_type',
            'ubn_of_birth', 'location_of_birth_id', 'pedigree_register_id', 'scrapie_genotype'];

        foreach ($columnVars as $columnVar) {
            $sql = "UPDATE animal SET $columnVar = v.new_$columnVar
                    FROM(
                          SELECT a.id as animal_id,
                                 a.$columnVar as old_$columnVar, amt.$columnVar as new_$columnVar
                          FROM animal_migration_table amt
                            INNER JOIN animal a ON a.uln_number = amt.uln_number
                          WHERE a.name ISNULL AND a.date_of_birth = amt.date_of_birth
                                AND amt.$columnVar NOTNULL AND (a.$columnVar <> amt.$columnVar OR a.$columnVar ISNULL)
                    ) AS v(animal_id, old_$columnVar, new_$columnVar) WHERE animal.id = v.animal_id";

            $title = 'Updating '.$columnVar.' values of animals with same uln and dateOfBirth but without vsmId';
            $this->updateBySql($title, $sql);
        }


        //2. Update animalId and genderInDatabase values in animal_migration_table. WARNING DO THIS BEFORE UPDATING GENDERS

        $sql = "UPDATE animal_migration_table SET animal_id = v.animal_id, gender_in_database = v.gender_old
                    FROM (
                           SELECT amt.vsm_id, a.id as animal_id, a.gender as gender_old
                           FROM animal_migration_table amt
                             INNER JOIN animal a ON a.uln_number = amt.uln_number
                           WHERE a.name ISNULL AND a.date_of_birth = amt.date_of_birth
                                 AND (amt.animal_id ISNULL OR amt.animal_id <> a.id OR
                                      amt.gender_in_database ISNULL OR amt.gender_in_database <> a.gender
                                 )
                    ) AS v(vsm_id, animal_id, gender_old) WHERE animal_migration_table.vsm_id = v.vsm_id";
        $this->updateBySql('Update animalId and genderInDatabase values in animal_migration_table', $sql);


        //3. Update gender of neuters. Leave the gender of animals that are not neuters
        $this->fixGenderOfNeutersByMigrationValues();


        //4. Update vsmId in animal. WARNING DO THIS STEP LAST!
        //   BECAUSE vsmId/name ISNULL condition is used to find these animals in the previous update queries in this function.

        $sql = 'UPDATE animal SET name = CAST(v.vsm_id AS TEXT)
                FROM (
                       SELECT amt.vsm_id, a.id as animal_id
                       FROM animal_migration_table amt
                         INNER JOIN animal a ON a.uln_number = amt.uln_number
                       WHERE a.name ISNULL AND a.date_of_birth = amt.date_of_birth
                ) AS v(vsm_id, animal_id) WHERE animal.id = v.animal_id';
        $this->updateBySql('Finally set vsmId on animals for new animals joined on uln & dateOfBirth', $sql);
    }


    private function fixGenderOfNeutersByMigrationValues()
    {
        $this->writeLn('=== Fix Neuters in animal table by new genders in animal_migration_table 
        with same uln, dateOfBirth but no vsmId ===');

        $totalRecordsUpdated = 0;

        $queries = [
            'Inserting genderHistory first and using that to regender animals ... ' =>
                "INSERT INTO gender_history_item (animal_id, log_date, previous_gender, new_gender)
                  SELECT a.id as animal_id, NOW(), a.type, yy.type
                  FROM animal_migration_table amt
                    INNER JOIN animal a ON a.uln_number = amt.uln_number AND a.date_of_birth = amt.date_of_birth
                    LEFT JOIN (VALUES ('MALE', 'Ram'),('FEMALE', 'Ewe'),('NEUTER','Neuter')) AS yy(gender, type) ON amt.gender_in_file = yy.gender
                  WHERE a.name ISNULL AND a.gender = 'NEUTER' AND amt.gender_in_file <> 'NEUTER'",

            'Updating gender of neuters based on data just inserted in genderHistory ...' =>
                "UPDATE animal SET type = v.new_type, gender = v.new_gender
                  FROM (
                      SELECT g.animal_id, new_gender as new_type, yy.gender as new_gender
                      FROM animal a
                          INNER JOIN gender_history_item g ON a.id = g.animal_id AND DATE(g.log_date) = DATE(NOW()) AND g.previous_gender = a.type
                          LEFT JOIN (VALUES ('MALE', 'Ram'),('FEMALE', 'Ewe'),('NEUTER','Neuter')) AS yy(gender, type) ON g.new_gender = yy.type
                      WHERE g.new_gender <> 'Neuter' AND g.new_gender <> 'NEUTER'   
                  ) AS v(animal_id, new_type, new_gender) WHERE animal.id = v.animal_id",

        ];

        foreach ($queries as $title => $query) {
            $totalRecordsUpdated += $this->updateBySql($title, $query);
        }

        if ($totalRecordsUpdated > 0) {
            DatabaseDataFixer::fixGenderTables($this->conn, $this->cmdUtil);
        }
    }


    private function migrateNewAnimals()
    {
        $this->updateAnimalIdsInMigrationTable();
        DoctrineUtil::updateTableSequence($this->conn, ['animal']);

        $sql = "INSERT INTO animal (name, uln_country_code, uln_number, pedigree_country_code, pedigree_number,
                  animal_order_number, nickname, gender, type, date_of_birth,
                  breed_code, ubn_of_birth, location_of_birth_id, pedigree_register_id, breed_type,
                  scrapie_genotype, animal_category, animal_type, is_alive,
                  is_import_animal, is_export_animal, is_departed_animal)
                  SELECT CAST(vsm_id AS TEXT) as vsm_id, amt.uln_country_code, amt.uln_number, amt.pedigree_country_code, amt.pedigree_number,
                    amt.animal_order_number, amt.nickname, gender_in_file as gender, yy.type, amt.date_of_birth,
                    amt.breed_code, amt.ubn_of_birth, amt.location_of_birth_id, amt.pedigree_register_id, amt.breed_type,
                    amt.scrapie_genotype, 3 as animal_category, 3 as animal_type, false as is_alive,
                    false as is_import_animal, false as is_export_animal, false as is_departed_animal
                  FROM animal_migration_table amt
                    LEFT JOIN animal a ON a.id =  amt.animal_id
                    LEFT JOIN (VALUES ('MALE', 'Ram'),('FEMALE', 'Ewe'),('NEUTER','Neuter')) AS yy(gender, type) ON amt.gender_in_file = yy.gender
                  WHERE a.id ISNULL AND amt.uln_number NOTNULL";
        $this->updateBySql('Migrating new animals from animal_migration_table to animal table ...', $sql);

        //Just to make sure the queries don't overlap
        sleep(2);

        DatabaseDataFixer::fillMissingAnimalChildTableRecords($this->conn, $this->cmdUtil);

        DoctrineUtil::updateTableSequence($this->conn, ['animal']);
        $this->updateAnimalIdsInMigrationTable();
    }


    /**
     * @return int
     */
    private function updateAnimalIdsInMigrationTable()
    {
        return SqlUtil::updateWithCount($this->conn, AnimalTableImporter::getUpdateIncongruentAnimalIdsSqlQuery());
    }


    private function updateIncongruentParentIdsInAnimalMigrationTable()
    {
        foreach (AnimalTableImporter::getQueriesToUpdateIncongreuentParentIdsInAnimalMigrationTable() as $title => $sql) {
            $this->updateBySql($title, $sql);
        }
    }


    private function updateParentIdsInAnimalTable()
    {
        $queries = [
            'Update parent_father_ids in animal table with the father_ids in animal_migration_table ...' =>
                "UPDATE animal SET parent_father_id = v.father_id
                    FROM (
                           SELECT a.id, amt.father_id
                           FROM animal a
                             INNER JOIN animal_migration_table amt ON a.id = amt.animal_id
                           WHERE parent_father_id ISNULL AND amt.father_id NOTNULL
                         ) AS v(animal_id, father_id) WHERE animal.id = v.animal_id",

            'Update parent_mother_ids in animal table with the mother_ids in animal_migration_table ...' =>
                "UPDATE animal SET parent_mother_id = v.mother_id
                    FROM (
                           SELECT a.id, amt.mother_id
                           FROM animal a
                             INNER JOIN animal_migration_table amt ON a.id = amt.animal_id
                           WHERE parent_mother_id ISNULL AND amt.mother_id NOTNULL
                         ) AS v(animal_id, mother_id) WHERE animal.id = v.animal_id",
        ];

        foreach ($queries as $title => $query) {
            $this->updateBySql($title, $query);
        }
    }
}