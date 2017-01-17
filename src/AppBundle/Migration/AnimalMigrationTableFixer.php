<?php


namespace AppBundle\Migration;


use AppBundle\Util\CommandUtil;
use Doctrine\DBAL\Connection;

class AnimalMigrationTableFixer
{

    /**
     * @param CommandUtil $cmdUtil
     * @param Connection $conn
     */
    public static function fillEmptyUlnsByGivenUlnLength(CommandUtil $cmdUtil, Connection $conn)
    {
        //Settings
        $minUlnNumberLengthDefault = 8;
        $dateOfBirthBeforeDate = '2010-01-01'; //Format YYYY-MM-DD, Date before standardized ulnNumbers with 12 length

        do{
            $isInputValid = false;
            $minUlnNumberLength = $cmdUtil->generateQuestion('Insert minimum ulnNumberLength (>=5, default = '.$minUlnNumberLengthDefault.')', $minUlnNumberLengthDefault);

            if(ctype_digit($minUlnNumberLength) || is_int($minUlnNumberLength)) {
                if(intval($minUlnNumberLength) >= 5) {
                    $isInputValid = true;
                } else {
                    $cmdUtil->writeln('Integer must be at least 5 digits');
                }

            } else {
                $cmdUtil->writeln('Input must be an integer');
            }
        } while(!$isInputValid);


        $dateOfBirthFilter = " AND date_of_birth < '".$dateOfBirthBeforeDate."' ";
        $dateFilterText = 'EXCLUDE';
        $alsoProcessAnimalsBornAfterFilterDate = $cmdUtil->generateConfirmationQuestion('Also process animals born after and on '.$dateOfBirthBeforeDate.'? (y/n, default = no)');
        if($alsoProcessAnimalsBornAfterFilterDate){
            $dateOfBirthFilter = '';
            $dateFilterText = 'INCLUDE';
        };


        $sql = "SELECT SUBSTR(uln_origin, 1,2) as uln_country_code, SUBSTR(uln_origin, 4) as uln_number, date_of_birth
				FROM animal_migration_table
				WHERE uln_number ISNULL
					  --For ulnOrigins with a separation space
					  AND SUBSTR(uln_origin, 1,2) ~ '^[A-Z]' --First 2 chars must be only capital letters
					  AND SUBSTR(uln_origin, 3,1) = ' '
					  AND SUBSTR(uln_origin, 4) ~ '^(-)?[0-9]+$' -- Last chars must be only digits
					  AND LENGTH(SUBSTR(uln_origin, 4)) >= ".$minUlnNumberLength." -- The ulnNumber must be at least this long
			          ".$dateOfBirthFilter."
				UNION
				SELECT SUBSTR(uln_origin, 1,2) as uln_country_code, SUBSTR(uln_origin, 3) as uln_number, date_of_birth
				FROM animal_migration_table
				WHERE uln_number ISNULL
					  --For ulnOrigins without a separation space
					  AND SUBSTR(uln_origin, 1,2) ~ '^[A-Z]' --First 2 chars must be only capital letters
					  AND SUBSTR(uln_origin, 3,1) <> ' '
					  AND SUBSTR(uln_origin, 3) ~ '^(-)?[0-9]+$' -- Last chars must be only digits
					  AND LENGTH(SUBSTR(uln_origin, 4)) >= ".$minUlnNumberLength." -- The ulnNumber must be at least this long
					  ".$dateOfBirthFilter;
        $results = $conn->query($sql)->fetchAll();

        $totalCount = count($results);
        if($totalCount == 0) {
            $cmdUtil->writeln('There are no valid ulnOrigins without an extracted ulnCountryCode and ulnNumber in the AnimalMigrationTable for the given ulnNumber length of '.$minUlnNumberLength);
            return;
        } else {
            $cmdUtil->writeln($totalCount.' animals found');
        }


        if(!$cmdUtil->generateConfirmationQuestion('You chose a minimum ulnNumber length of '.$minUlnNumberLength.' and to '.$dateFilterText.' animals born on or after '.$dateOfBirthBeforeDate.'. Is this correct? (y/n, default = no)')){
            $cmdUtil->writeln('ABORTED');
            return;
        };



        $cmdUtil->writeln('Updating  '.$totalCount.' empty ulns...');

        //Update AnimalMigrationTable
        $sql = "UPDATE animal_migration_table SET uln_country_code = SUBSTR(uln_origin, 1,2), uln_number = SUBSTR(uln_origin, 4)
				WHERE uln_number ISNULL
					  --For ulnOrigins with a separation space
					  AND SUBSTR(uln_origin, 1,2) ~ '^[A-Z]' --First 2 chars must be only capital letters
					  AND SUBSTR(uln_origin, 3,1) = ' '
					  AND SUBSTR(uln_origin, 4) ~ '^(-)?[0-9]+$' -- Last chars must be only digits
					  AND LENGTH(SUBSTR(uln_origin, 4)) >= ".$minUlnNumberLength." -- The ulnNumber must be at least this long
					  ".$dateOfBirthFilter;
        $conn->exec($sql);

        //Update AnimalMigrationTable
        $sql = "UPDATE animal_migration_table SET uln_country_code = SUBSTR(uln_origin, 1,2), uln_number = SUBSTR(uln_origin, 3)
				WHERE uln_number ISNULL
					  --For ulnOrigins without a separation space
					  AND SUBSTR(uln_origin, 1,2) ~ '^[A-Z]' --First 2 chars must be only capital letters
					  AND SUBSTR(uln_origin, 3,1) <> ' '
					  AND SUBSTR(uln_origin, 3) ~ '^(-)?[0-9]+$' -- Last chars must be only digits
					  AND LENGTH(SUBSTR(uln_origin, 4)) >= ".$minUlnNumberLength." -- The ulnNumber must be at least this long
					  ".$dateOfBirthFilter;
        $conn->exec($sql);

        $cmdUtil->writeln($totalCount. ' records in animalMigrationTable updated!');
    }


    /**
     * @param CommandUtil $cmdUtil
     * @param Connection $conn
     */
    public static function updateAnimalIdsInMigrationTable(CommandUtil $cmdUtil, Connection $conn)
    {
        $cmdUtil->writeln('Finding missing animalIds in AnimalMigrationTable...');
        
        $sqlFindBlankAnimalIds = "SELECT a.id AS animal_id, m.id AS migration_table_id FROM animal_migration_table m
                                    LEFT JOIN animal a ON a.name = CAST (m.vsm_id AS TEXT)
                                  WHERE m.animal_id ISNULL AND a.id NOTNULL";
        $count = $conn->query($sqlFindBlankAnimalIds)->rowCount();

        if($count) {
            $cmdUtil->writeln('Filling '.$count.' missing animalIds in AnimalMigrationTable...');
            $sqlFillBlankAnimalIds = "UPDATE animal_migration_table as t SET animal_id = v.animal_id
                                    FROM
                                      (".$sqlFindBlankAnimalIds."
                                    ) as v(animal_id, migration_table_id) WHERE t.id = v.migration_table_id";
            $conn->exec($sqlFillBlankAnimalIds);
            $cmdUtil->writeln('Done!');
        } else {
            $cmdUtil->writeln('No missing animalIds in AnimalMigrationTable!');
        }



        $cmdUtil->writeln('Finding incorrect animalIds in AnimalMigrationTable...');

        $sqlFindIncorrectAnimalIds = "SELECT a.id AS animal_id, m.id AS migration_table_id FROM animal_migration_table m
                                    LEFT JOIN animal a ON a.name = CAST (m.vsm_id AS TEXT)
                                  WHERE m.animal_id <> a.id";
        $count = $conn->query($sqlFindIncorrectAnimalIds)->rowCount();

        if($count) {
            $cmdUtil->writeln('Updating '.$count.' incorrect animalIds in AnimalMigrationTable...');
            $sqlUpdateIncorrectAnimalIds = "UPDATE animal_migration_table as t SET animal_id = v.animal_id
                                    FROM
                                      (".$sqlFindIncorrectAnimalIds."
                                    ) as v(animal_id, migration_table_id) WHERE t.id = v.migration_table_id";
            $conn->exec($sqlUpdateIncorrectAnimalIds);
            $cmdUtil->writeln('Done!');
        } else {
            $cmdUtil->writeln('No incorrect animalIds in AnimalMigrationTable!');
        }        
        
    }


    /**
     * @param CommandUtil $cmdUtil
     * @param Connection $conn
     */
    public static function updateGenderInDatabaseInMigrationTable(CommandUtil $cmdUtil, Connection $conn)
    {
        $cmdUtil->writeln('Update incorrect genderInDatabase values in AnimalMigrationTable...');

        $sqlFindIncorrectGenders = "SELECT a.gender, m.id AS migration_table_id FROM animal_migration_table m
                                    INNER JOIN animal a ON a.name = CAST (m.vsm_id AS TEXT)
                                  WHERE m.gender_in_database <> a.gender OR gender_in_database ISNULL ";
        $count = $conn->query($sqlFindIncorrectGenders)->rowCount();

        if($count) {
            $cmdUtil->writeln('Updating '.$count.' incorrect genderInDatabase values in AnimalMigrationTable...');
            $sqlUpdateIncorrectGenders = "UPDATE animal_migration_table as t SET gender_in_database = v.gender
                                    FROM
                                      (".$sqlFindIncorrectGenders."
                                    ) as v(gender, migration_table_id) WHERE t.id = v.migration_table_id";
            $conn->exec($sqlUpdateIncorrectGenders);
            $cmdUtil->writeln('Done!');
        } else {
            $cmdUtil->writeln('No incorrect genderInDatabase values in AnimalMigrationTable!');
        }

    }


    /**
     * @param CommandUtil $cmdUtil
     * @param Connection $conn
     */
    public static function updateParentIdsInMigrationTable(CommandUtil $cmdUtil, Connection $conn)
    {
        foreach (['father', 'mother'] as $parentType) {
            $cmdUtil->writeln('Update incorrect '.$parentType.'_id values in AnimalMigrationTable...');

            $sqlFindIncorrectParentIds = "SELECT a.id AS ".$parentType."_id, m.id AS migration_table_id FROM animal_migration_table m
                                        INNER JOIN animal a ON a.name = CAST (m.".$parentType."_vsm_id AS TEXT)
                                      WHERE m.".$parentType."_id ISNULL OR m.".$parentType."_id <> a.id";
            $count = $conn->query($sqlFindIncorrectParentIds)->rowCount();

            if($count) {
                $cmdUtil->writeln('Updating '.$count.' incorrect '.$parentType.'_id values in AnimalMigrationTable...');
                $sqlUpdateIncorrectParentIds = "UPDATE animal_migration_table as t SET ".$parentType."_id = v.parent_id
                                    FROM
                                      (".$sqlFindIncorrectParentIds."
                                    ) as v(parent_id, migration_table_id) WHERE t.id = v.migration_table_id";
                $conn->exec($sqlUpdateIncorrectParentIds);
                $cmdUtil->writeln('Done!');
            } else {
                $cmdUtil->writeln('No incorrect '.$parentType.'_id values in AnimalMigrationTable!');
            }
        }
        


    }
}