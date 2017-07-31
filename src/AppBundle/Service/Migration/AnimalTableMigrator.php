<?php

namespace AppBundle\Service\Migration;

use AppBundle\Component\MessageBuilderBase;
use AppBundle\Entity\DeclareTagReplace;
use AppBundle\Enumerator\ActionType;
use AppBundle\Enumerator\AnimalType;
use AppBundle\Enumerator\RecoveryIndicatorType;
use AppBundle\Enumerator\RequestStateType;
use AppBundle\Service\DataFix\DuplicateAnimalsFixer;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DatabaseDataFixer;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
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

    /** @var DuplicateAnimalsFixer */
    private $duplicateAnimalsFixer;

    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir, self::BATCH_SIZE);
    }


    /** @inheritdoc */
    public function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn('====== PRE migration fixes ======');
        $this->preparationFixes();
        $this->mergeDuplicateAnimalsByVsmIdAndTagReplaces($this->cmdUtil);
        $this->fixGenderOfNeutersByMigrationValues();

        $this->writeLn('====== Migrate animals ======');
        $this->migrateNewAnimalsJoinedOnUlnAndDateOfBirth();
        $this->migrateNewAnimals();
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function fix(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        $this->writeLn('====== Fix animals ======');
        //NOTE the order is important!
        $this->mergeDuplicateAnimalsByVsmIdAndTagReplaces($this->cmdUtil);
        $this->getDuplicateAnimalsFixer()->fixDuplicateAnimalsGroupedOnUlnVsmIdDateOfBirth($this->cmdUtil);
        $this->mergeTagReplacedAnimalsWithoutDeclareTagReplaces();
        $this->removeUlnAndAnimalIdForDuplicateAnimalsWithConstructedUln();

        $this->writeLn('====== Set parents ======');
        $this->updateIncongruentParentIdsInAnimalMigrationTable();
        $this->updateParentIdsInAnimalTable();

        $this->writeLn('====== Fill other desired values ======');
        $this->fillMissingValues();
    }


    private function preparationFixes()
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


    /**
     * @param CommandUtil $cmdUtil
     */
    public function mergeDuplicateAnimalsByVsmIdAndTagReplaces(CommandUtil $cmdUtil = null)
    {
        parent::run($cmdUtil);

        $sql = "SELECT
                  uln_number_replacement, p.uln_number, p.id as primary_animal_id,
                  uln_number_to_replace, s.uln_number, s.id as secondary_animal_id
                FROM declare_tag_replace t
                  INNER JOIN declare_base b ON b.id = t.id
                  INNER JOIN animal p ON p.uln_number = uln_number_replacement
                  INNER JOIN animal s ON s.uln_number = uln_number_to_replace
                WHERE request_state <> 'REVOKED' AND (
                  uln_number_replacement IN (
                    --uln_number of animals with duplicate vsmId/name
                    SELECT uln_number
                    FROM animal a
                      INNER JOIN (
                                   SELECT name
                                   FROM animal
                                   WHERE name NOTNULL
                                   GROUP BY name HAVING COUNT(*) > 1
                                 )g ON g.name = a.name
                  )
                  OR uln_number_to_replace IN (
                    --uln_number of animals with duplicate vsmId/name
                    SELECT uln_number
                    FROM animal a
                      INNER JOIN (
                                   SELECT name
                                   FROM animal
                                   WHERE name NOTNULL
                                   GROUP BY name HAVING COUNT(*) > 1
                                 )g ON g.name = a.name
                  )
                )";

        $duplicatePairs = $this->conn->query($sql)->fetchAll();
        $duplicatePairsCount = count($duplicatePairs);

        if ($duplicatePairsCount === 0) {
            return;
        }

        $title = 'Merging Duplicate Animals by vsmId/name ...';
        $this->cmdUtil->writeln($title);

        $successfulMerges = 0;
        $failedMerges = 0;

        $this->cmdUtil->setStartTimeAndPrintIt($duplicatePairsCount, 1, $title);
        foreach ($duplicatePairs as $duplicatePair) {
            $primaryAnimalId = $duplicatePair['primary_animal_id'];
            $secondaryAnimalId = $duplicatePair['secondary_animal_id'];
            $isMerged = $this->getDuplicateAnimalsFixer()->mergeAnimalPairByIds($primaryAnimalId,$secondaryAnimalId);

            if ($isMerged) {
                $successfulMerges++;
            } else {
                $failedMerges++;
            }
            $this->cmdUtil->advanceProgressBar(1,
                'Duplicate animal merges succeeded|failed: '.$successfulMerges.'|'.$failedMerges);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @return DuplicateAnimalsFixer
     */
    private function getDuplicateAnimalsFixer()
    {
        if($this->duplicateAnimalsFixer === null) {
            $this->duplicateAnimalsFixer = new DuplicateAnimalsFixer($this->em);
        }
        return $this->duplicateAnimalsFixer;
    }


    private function mergeTagReplacedAnimalsWithoutDeclareTagReplaces()
    {
        $sql = "SELECT
                  amt.vsm_id, a.id as primary_animal_id, secondary.id as secondary_animal_id,
                  a.uln_country_code as uln_country_code_replacement, a.uln_number as uln_number_replacement,
                  secondary.uln_country_code as uln_country_code_to_replace, secondary.uln_number as uln_number_to_replace,
                  uln_origin
                FROM animal a
                  INNER JOIN animal_migration_table amt ON amt.vsm_id = CAST(name AS INTEGER)
                  INNER JOIN animal secondary ON secondary.name = a.name
                  INNER JOIN (
                               SELECT name
                               FROM animal
                               WHERE name NOTNULL
                               GROUP BY name HAVING COUNT(*) = 2
                             )g ON g.name = a.name
                WHERE CONCAT(a.uln_country_code,' ',a.uln_number) = amt.uln_origin
                  AND secondary.id <> a.id
                ORDER BY a.name, a.id";
        $results = $this->conn->query($sql)->fetchAll();
        $totalCount = count($results);

        if ($totalCount === 0) { return; }

        $this->cmdUtil->writeln('=== Merging tag replaced animals without declare tag replaces');

        $this->cmdUtil->writeln('A few animals ('.$totalCount.') have new ulns compared to the old import file.
        The vsmId and dateOfBirth are similar, but the uln is different.
        There are also no tagReplaces available.
        We know the new ulns in the new import file are the correct ulns,
        because they match the ulns of the neuter synced animals.');

        $this->cmdUtil->setStartTimeAndPrintIt($totalCount, 1);

        $successfulMerges = 0;
        $failedMerges = 0;

        foreach ($results as $result) {
            $primaryAnimalId = intval($result['primary_animal_id']);
            $secondaryAnimalId = intval($result['secondary_animal_id']);

            $requestId = MessageBuilderBase::getNewRequestId();


            //1. Save as declare tag replace

            $declareTagReplace = new DeclareTagReplace();
            $declareTagReplace
                ->setAnimal($this->animalRepository->find($primaryAnimalId))
                ->setUlnCountryCodeToReplace($result['uln_country_code_to_replace'])
                ->setUlnNumberToReplace($result['uln_number_to_replace'])
                ->setUlnCountryCodeReplacement($result['uln_country_code_replacement'])
                ->setUlnNumberReplacement($result['uln_number_replacement'])
                ->setAnimalOrderNumberToReplace(StringUtil::getLast5CharactersFromString($result['uln_number_to_replace']))
                ->setAnimalOrderNumberReplacement(StringUtil::getLast5CharactersFromString($result['uln_number_replacement']))
                ->setAnimalType(AnimalType::sheep)
                ->setReplaceDate(new \DateTime(self::BLANK_DATE_FILLER))
                ->setRequestState(RequestStateType::IMPORTED)
                ->setRelationNumberKeeper(RequestStateType::IMPORTED)
                ->setUbn(RequestStateType::IMPORTED)
                ->setRequestId($requestId)
                ->setMessageId($requestId)
                ->setAction(ActionType::V_MUTATE)
                ->setRecoveryIndicator(RecoveryIndicatorType::N)
                ->setActionBy($this->getDeveloper())
                ;
            $this->em->persist($declareTagReplace);
            $this->em->flush();


            //2. Merge animals

            $isMerged = $this->getDuplicateAnimalsFixer()->mergeAnimalPairByIds($primaryAnimalId,$secondaryAnimalId);

            if ($isMerged) {
                $successfulMerges++;
            } else {
                $failedMerges++;
            }

            $this->cmdUtil->advanceProgressBar(1,
                'Duplicate animal merges succeeded|failed: '.$successfulMerges.'|'.$failedMerges);
        }
        $this->cmdUtil->setEndTimeAndPrintFinalOverview();

        //Don't forget to update the parent data in both the animal_migration_table and animal table.
    }


    private function migrateNewAnimalsJoinedOnUlnAndDateOfBirth()
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
                             INNER JOIN animal dad ON dad.id = amt.father_id
                           WHERE a.parent_father_id ISNULL AND amt.father_id NOTNULL AND dad.type = 'Ram'
                         ) AS v(animal_id, father_id) WHERE animal.id = v.animal_id",

            'Update parent_mother_ids in animal table with the mother_ids in animal_migration_table ...' =>
                "UPDATE animal SET parent_mother_id = v.mother_id
                    FROM (
                           SELECT a.id, amt.mother_id
                           FROM animal a
                             INNER JOIN animal_migration_table amt ON a.id = amt.animal_id
                             INNER JOIN animal mom ON mom.id = amt.mother_id
                           WHERE a.parent_mother_id ISNULL AND amt.mother_id NOTNULL AND mom.type = 'Ewe'
                         ) AS v(animal_id, mother_id) WHERE animal.id = v.animal_id",
        ];

        foreach ($queries as $title => $query) {
            $this->updateBySql($title, $query);
        }
    }


    private function fillMissingValues()
    {
        $this->writeLn('=== Fill missing values in animal table by values in animal_migration_table ===');

        $columnVars = ['nickname',
//            'pedigree_country_code', 'pedigree_number', 'breed_code', 'breed_type',
//            'ubn_of_birth', 'location_of_birth_id', 'pedigree_register_id', 'scrapie_genotype'
        ];

        foreach ($columnVars as $columnVar) {
            $sql = "UPDATE animal SET $columnVar = v.new_$columnVar
                    FROM(
                          SELECT a.id as animal_id,
                                 --a.$columnVar as old_$columnVar,
                                 amt.$columnVar as new_$columnVar
                          FROM animal_migration_table amt
                            INNER JOIN animal a ON a.id = amt.animal_id
                          WHERE amt.$columnVar NOTNULL AND a.$columnVar ISNULL
                    ) AS v(
                            animal_id,
                            --old_$columnVar,
                            new_$columnVar 
                          ) WHERE animal.id = v.animal_id";

            $title = 'Updating '.$columnVar.' values of animals with same uln and dateOfBirth but without vsmId';
            $this->updateBySql($title, $sql);
        }
    }



    private function removeUlnAndAnimalIdForDuplicateAnimalsWithConstructedUln()
    {
        $this->writeLn('=== Remove duplicate animals from animal_migration_table with a constructed uln ===');

        $queries = [
            //Run this query BEFORE editing the animal_migration_table records!
          'Save the primary-&-secondary vsm_id pair ...' =>
              "INSERT INTO vsm_id_group (primary_vsm_id, secondary_vsm_id) 
                  SELECT
                    main.vsm_id as primary_vsm_id,--main.is_uln_updated
                    secondary.vsm_id as secondary_vsm_id--, secondary.is_uln_updated, g.animal_id,
                  FROM animal_migration_table secondary
                    INNER JOIN (
                                 SELECT animal_id,
                                   SUM(CAST(is_uln_updated AS INTEGER)) = 1 as has_one_uln_updated_animal_in_set
                                 FROM animal_migration_table
                                 WHERE animal_id NOTNULL
                                 GROUP BY animal_id HAVING  COUNT(*) = 2
                               )g ON g.animal_id = secondary.animal_id
                    INNER JOIN animal_migration_table main ON main.animal_id = g.animal_id AND main.id <> secondary.id
                    LEFT JOIN vsm_id_group v ON v.secondary_vsm_id = CAST(secondary.vsm_id AS TEXT)
                  WHERE
                    --The set has 2 animals of which one has a constructed uln.
                    --Keep the one with the original uln as the primary animal.
                    g.has_one_uln_updated_animal_in_set AND secondary.is_uln_updated
                    --Only insert new vsm_id_group records if the secondary_vsm_id has no record yet
                    AND v.id ISNULL",

            'Remove uln_origin, stn_origin, uln and animal_id from duplicate animal 
                and save the uln & stn in the deleted columns ...' =>
                "UPDATE animal_migration_table
                    SET
                      deleted_uln_origin = uln_origin, deleted_stn_origin = stn_origin,
                      uln_origin = NULL, stn_origin = NULL, uln_country_code = NULL, uln_number = NULL,
                      animal_id = NULL
                    WHERE id IN (
                      SELECT id
                      FROM animal_migration_table m
                        INNER JOIN (
                                     SELECT animal_id,
                                       SUM(CAST(is_uln_updated AS INTEGER)) = 1 as has_one_uln_updated_animal_in_set
                                     FROM animal_migration_table
                                     WHERE animal_id NOTNULL
                                     GROUP BY animal_id HAVING  COUNT(*) = 2
                                   )g ON g.animal_id = m.animal_id
                      WHERE
                        --The set has 2 animals of which one has a constructed uln.
                        --Keep the one with the original uln as the primary animal.
                        g.has_one_uln_updated_animal_in_set AND is_uln_updated
                    )",
        ];

        foreach ($queries as $title => $query) {
            $this->updateBySql($title, $query);
        }
    }
}