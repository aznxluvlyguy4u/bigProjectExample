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
}