<?php


namespace AppBundle\Util;


use AppBundle\Constant\JsonInputConstant;
use AppBundle\Enumerator\BreedTypeDutch;
use AppBundle\Enumerator\ColumnType;
use AppBundle\Enumerator\DutchGender;
use AppBundle\Enumerator\GenderType;
use AppBundle\Enumerator\RequestTypeIRDutchInformal;
use AppBundle\Enumerator\RequestTypeIRDutchOfficial;
use AppBundle\Service\Report\ReportServiceWithBreedValuesBase;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class SqlUtil
{
    const OR_FILTER = ' OR ';
    const NULL = 'NULL';
    const DATE_FORMAT = 'Y-m-d H:i:s';


    /**
     * @param Connection $conn
     * @param $tableName
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function bumpPrimaryKeySeq(Connection $conn, $tableName)
    {
        $tableName = strtolower($tableName);
        $sql = "SELECT setval('".$tableName."_id_seq', (SELECT MAX(id) FROM ".$tableName.")+1)";
        $conn->exec($sql);
    }


    /**
     * @param mixed $value
     * @param boolean $includeQuotes
     * @return string
     */
    public static function getNullCheckedValueForSqlQuery($value, $includeQuotes)
    {
        if($value != null && $value != '') {
            return $includeQuotes ? StringUtil::getNullAsStringOrWrapInQuotes($value) : StringUtil::getNullAsString($value);
        } else {
            return self::NULL;
        }
    }


    /**
     * @param mixed $value
     * @param array $searchArray
     * @param boolean $includeQuotes
     * @return string
     */
    public static function getSearchArrayCheckedValueForSqlQuery($value, $searchArray, $includeQuotes)
    {
        if(array_key_exists($value, $searchArray)) {
            return $includeQuotes ? StringUtil::getNullAsStringOrWrapInQuotes($searchArray[$value]) : StringUtil::getNullAsString($searchArray[$value]);
        } else {
            return self::NULL;
        }
    }


    /**
     * @param array $sqlResultsArray
     * @param string $variable1ToGroupBy
     * @param string $variable2ToGroupBy
     * @return array
     */
    public static function createGroupedSearchArrayFromSqlResults($sqlResultsArray, $variable1ToGroupBy, $variable2ToGroupBy = null)
    {
        $duplicatesSearchArray = [];
        //Create grouped searchArray
        foreach ($sqlResultsArray as $result) {

            $keyToGroupBy = $result[$variable1ToGroupBy];
            if($variable2ToGroupBy != null) {
                $keyToGroupBy = $result[$variable1ToGroupBy].$result[$variable2ToGroupBy];
            }

            if(!array_key_exists($keyToGroupBy, $duplicatesSearchArray)) {
                $duplicatesSearchArray[$keyToGroupBy] = [];
            }

            $group = $duplicatesSearchArray[$keyToGroupBy];
            $group[] = $result;
            $duplicatesSearchArray[$keyToGroupBy] = $group;
        }
        return $duplicatesSearchArray;
    }


    /**
     * @param ObjectManager $em
     * @param string $tableNameInSnakeCase
     * @param string $outputFolderPath
     * @param string $fileName
     * @param OutputInterface|null $output
     * @param CommandUtil|null $cmdUtil
     */
    public static function exportToCsv(ObjectManager $em, $tableNameInSnakeCase, $outputFolderPath, $fileName,
                                       OutputInterface $output = null, CommandUtil $cmdUtil = null)
    {
        if(!is_string($tableNameInSnakeCase) || !is_string($outputFolderPath) || !is_string($fileName)) { return; }

        $columnSeparator = ';';
        $rowSeparator = "\n"; //newLine
        $outputFilePath = $outputFolderPath.'/'.$fileName;

        if($output != null) { $output->writeln('Retrieving data from table '.$tableNameInSnakeCase); }

        $sql = "SELECT * FROM ".$tableNameInSnakeCase; //$tableNameInSnakeCase
        $results = $em->getConnection()->query($sql)->fetchAll();

        if($output != null) { $output->writeln('Data retrieved!'); }
        
        if(count($results) == 0) { return; }
        NullChecker::createFolderPathIfNull($outputFolderPath);

        $columnHeaders = array_keys($results[0]);
        $row = '';
        foreach ($columnHeaders as $key) {
            $row = $row.$key.$columnSeparator;
        }
        file_put_contents($outputFilePath, $row.$rowSeparator, FILE_APPEND);
        if($output != null) { $output->writeln('Headers printed: '.$row); }

        if($output != null) { $output->writeln('Printing '.count($results).' rows'); }

        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt(count($results),1); }
        foreach ($results as $result) {
            $row = '';
            foreach ($columnHeaders as $key) {
                $value = $result[$key];
                if(is_bool($result[$key])) { $value = StringUtil::getBooleanAsString($value); }
                $row = $row.$value.$columnSeparator;
            }
            file_put_contents($outputFilePath, $row.$rowSeparator, FILE_APPEND);
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1); }
        }
        if($output != null) { $output->writeln('Csv exported!'); }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }


    /**
     * @param ObjectManager $em
     * @param string $tableNameInSnakeCase
     * @param array $columnHeaders
     * @param array $columnTypes
     * @param array $data
     * @param OutputInterface $output
     * @param CommandUtil $cmdUtil
     */
    public static function importFromCsv(ObjectManager $em, $tableNameInSnakeCase, array $columnHeaders, array $columnTypes, array $data, OutputInterface $output, CommandUtil $cmdUtil)
    {
        if(!is_array($columnHeaders) || !is_array($columnTypes) || !is_array($data) || !is_string($tableNameInSnakeCase)) { return; }
        if(count($data) == 0 || count($columnHeaders) == 0 || count($columnTypes) == 0) {
            $output->writeln('No data, import aborted');
            return;
        }

        /** @var Connection $conn */
        $conn = $count = $em->getConnection();

        //Remove empty last columnHeaders
        if(end($columnHeaders) == '') { array_pop($columnHeaders); }

        // Check if table is empty
        $sql = "SELECT COUNT(*) FROM ".$tableNameInSnakeCase;
        $count = $conn->query($sql)->fetch()['count'];

        $currentPrimaryKeys = [];
        if($count > 0) {
            $output->writeln('The '.$tableNameInSnakeCase.' table is not empty.');
            $clearTable = $cmdUtil->generateConfirmationQuestion('Delete the '.$tableNameInSnakeCase.' table and import (y) OR continue import with filled table? (n) ');

            if($clearTable) {
                $output->writeln('Clearing table '.$tableNameInSnakeCase);
                $sql = "DELETE FROM ".$tableNameInSnakeCase;
                $conn->exec($sql);
            } else {
                $sql = "SELECT id FROM ".$tableNameInSnakeCase;
                $results = $conn->query($sql)->fetchAll();
                foreach ($results as $result) {
                    $id = $result['id'];
                    $currentPrimaryKeys[$id] = $id;
                }
                $output->writeln('Currently '.count($currentPrimaryKeys).' records are filled in '.$tableNameInSnakeCase);
            }
        }

        //Importing data

        $headerRow = '';
        foreach ($columnHeaders as $columnHeader) {
            $headerRow = $headerRow.$columnHeader;
            if($columnHeader != end($columnHeaders)){ $headerRow = $headerRow.', '; }
        }


        $message = 'importing csv data into '.$tableNameInSnakeCase.' table, new|skipped: ';
        $cmdUtil->setStartTimeAndPrintIt(count($data), 1, $message);

        $idKey = array_search('id', $columnHeaders, true);
        $nicknameKey = array_search('nickname', $columnHeaders, true);

        $keys = array_keys($columnHeaders);
        $skippedCount = 0;
        $newCount = 0;

        foreach ($data as $record) {
            $valueSet = '';

            if(array_key_exists($record[$idKey], $currentPrimaryKeys)) {
                //Skip records already in the table
                $skippedCount++;
            } else {
                foreach ($keys as $key) {
                    //Surround with quotes or not depending on type
                    switch ($columnTypes[$key]) {
                        case ColumnType::BOOLEAN:   $includeQuotes = false; break;
                        case ColumnType::INTEGER:   $includeQuotes = false; break;
                        case ColumnType::STRING:    $includeQuotes = true; break;
                        case ColumnType::DATETIME:  $includeQuotes = true; break;
                        default:                    $includeQuotes = true; break;
                    }
                    $value = $record[$key];
                    if($key == $nicknameKey) {
                        $value = utf8_encode(StringUtil::escapeSingleApostrophes($value));
                    }

                    $valueSet = $valueSet.SqlUtil::getNullCheckedValueForSqlQuery($value, $includeQuotes);
                    if($key != end($keys)){ $valueSet = $valueSet.', '; }
                }
                $sql = "INSERT INTO ".$tableNameInSnakeCase." (".$headerRow.")VALUES(".$valueSet.")";
                $conn->exec($sql);
                $newCount++;
            }
            $cmdUtil->advanceProgressBar(1, $message.$newCount.'|'.$skippedCount);
        }
        $cmdUtil->setEndTimeAndPrintFinalOverview();
    }


    /**
     * @param array $idsArray
     * @param string $prefix
     * @return string
     */
    public static function getFilterStringByIdsArray($idsArray, $prefix = 'id')
    {
        $idFilterString = '';
        if(!is_array($idsArray)) { return $idFilterString; }
        if(count($idsArray) == 0) { return $idFilterString; }
        
        foreach ($idsArray as $id) {
            if(is_int($id) || ctype_digit($id)) {
                $idFilterString = $idFilterString.' '.$prefix.' = '.$id.self::OR_FILTER;
            }
        }
        return rtrim($idFilterString, self::OR_FILTER);
    }


    /**
     * @param array $results
     * @return array
     */
    public static function getColumnHeadersFromSqlResults($results)
    {
        $variables = [];
        if(!is_array($results)) { return $variables; }
        if(count($results) == 0) { return $variables; }

        return array_keys($results[0]);
    }


    /**
     * @param array $results
     * @return array
     */
    public static function groupSqlResultsByVariable($results)
    {
        $groupedResults = [];
        if(!is_array($results)) { return $groupedResults; }
        if(count($results) == 0) { return $groupedResults; }
        
        $columnHeaders = self::getColumnHeadersFromSqlResults($results);
        
        foreach ($results as $result) {
            foreach ($columnHeaders as $columnHeader) {
                
                if(array_key_exists($columnHeader, $groupedResults)) {
                    $group = $groupedResults[$columnHeader];
                } else {
                    $group = [];
                }
                $group[] = $result[$columnHeader];
                $groupedResults[$columnHeader] = $group;                                
            }
        }
        return $groupedResults;
    }


    /**
     * @param string|int $key
     * @param array $results
     * @return array
     */
    public static function groupSqlResultsGroupedBySingleVariable($key, $results)
    {
        $groupedResults = [];
        if(!is_array($results)) { return $groupedResults; }
        if(count($results) == 0) { return $groupedResults; }
        if(!array_key_exists($key, $results[0])) { return $groupedResults; }

        foreach ($results as $result) {
            if(array_key_exists($key, $groupedResults)) {
                $group = $groupedResults[$key];
            } else {
                $group = [];
            }
            $group[] = $result[$key];
            $groupedResults[$key] = $group;
        }
        return $groupedResults;
    }


    /**
     * @param string|int $key
     * @param array $results
     * @return array
     */
    public static function createSearchArrayByKey($key, $results)
    {
        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result[$key]] = $result;
        }
        return $searchArray;
    }


    /**
     * @param bool $isIntVal
     * @param bool $sortResults
     * @return array
     */
    public static function getSingleValueGroupedSqlResults($key, $results, $isIntVal = false, $sortResults = false)
    {
        $listOfValues = [];
        if(!is_array($results)) { return $listOfValues; }
        if(count($results) == 0) { return $listOfValues; }
        if(!array_key_exists($key, $results[0])) { return $listOfValues; }

        if($isIntVal) {
            foreach ($results as $result) {
                $value = intval($result[$key]);
                $listOfValues[$value] = $value;
            }
        } else {
            foreach ($results as $result) {
                $value = $result[$key];
                $listOfValues[$value] = $value;
            }
        }

        if($sortResults) {
            ksort($listOfValues);
        }

        return $listOfValues;
    }


    public static function getSingleValueGroupedFloatsFromSqlResults($key, $results, $sortResults = false)
    {
        $listOfValues = [];
        if(!is_array($results)) { return $listOfValues; }
        if(count($results) == 0) { return $listOfValues; }
        if(!array_key_exists($key, $results[0])) { return $listOfValues; }

        foreach ($results as $result) {
            $value = floatval($result[$key]);
            $listOfValues[] = $value;
        }

        if($sortResults) {
            sort($listOfValues);
        }

        return $listOfValues;
    }


    /**
     * @param string|int $key1
     * @param string|int $key2
     * @param array $results
     * @param boolean $key1IsInt
     * @param boolean $key2IsInt
     * @return array
     */
    public static function groupSqlResultsOfKey1ByKey2($key1, $key2, $results, $key1IsInt = false, $key2IsInt = false)
    {
        $groupedResults = [];
        if(!is_array($results)) { return $groupedResults; }
        if(count($results) == 0) { return $groupedResults; }
        if(!array_key_exists($key1, $results[0]) || !array_key_exists($key2, $results[0])) { return $groupedResults; }

        foreach ($results as $result) {
            $value1 = $key1IsInt ? intval($result[$key1]) : $result[$key1];
            $value2 = $key2IsInt ? intval($result[$key2]) : $result[$key2];

            $groupedResults[$value2] = $value1;
        }

        return $groupedResults;
    }


    /**
     * @param Connection $conn
     * @param string $tableName
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getMaxId(Connection $conn, $tableName)
    {
        $sql = "SELECT MAX(id) FROM ".$tableName;
        return $conn->query($sql)->fetch()['max'];
    }


    /**
     * @param Connection $conn
     * @param string $sql
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function updateWithCount(Connection $conn, $sql)
    {
        $sql = "WITH rows AS (
                  ".$sql."
                RETURNING 1
                )
                SELECT COUNT(*) AS count FROM rows;";
        return $conn->query($sql)->fetch()['count'];
    }


    /**
     * @param array $results
     * @param string $alias
     * @return string
     */
    public static function getUlnQueryFilter(array $results, $alias = '')
    {
        $filterString = '';
        $prefix = '';
        foreach ($results as $result)
        {
            $ulnCountryCode = $result[JsonInputConstant::ULN_COUNTRY_CODE];
            $ulnNumber = $result[JsonInputConstant::ULN_NUMBER];

            $filterString = $filterString . $prefix . '('.$alias.'uln_country_code = \''.$ulnCountryCode
                                                    .'\' AND '.$alias.'uln_number = \''.$ulnNumber.'\')';
            $prefix = ' OR ';
        }
        return $filterString;
    }


    /**
     * @param array $values
     * @param string $key
     * @param boolean $valueIsBetweenSingleQuotationMarks
     * @return null|string
     */
    public static function filterString($values = [], $key, $valueIsBetweenSingleQuotationMarks)
    {
        if(count($values) === 0) { return null; }

        if($valueIsBetweenSingleQuotationMarks) {
            return "(".$key." = '". implode("' OR ".$key." = '", $values) . "')";
        } else {
            return "(".$key." = ". implode(" OR ".$key." = ", $values) . ")";
        }
    }


    /**
     * @param array $values
     * @param boolean $valueIsBetweenSingleQuotationMarks
     * @return null|string
     */
    public static function valueString($values = [], $valueIsBetweenSingleQuotationMarks)
    {
        if(count($values) === 0) { return null; }

        $quotationMark = $valueIsBetweenSingleQuotationMarks ? "'" : '';
        return "($quotationMark".implode("$quotationMark),($quotationMark", $values) . "$quotationMark)";
    }

    /**
     * @param array $dutchByEnglishValues
     * @param bool $isOnlyCapitalizeFirstLetterKey
     * @param bool $isOnlyCapitalizeFirstLetterValue
     * @return string
     */
    public static function createSqlValuesString($dutchByEnglishValues, $isOnlyCapitalizeFirstLetterKey = false, $isOnlyCapitalizeFirstLetterValue = true)
    {
        $valuesString = '';
        $prefix = '';

        foreach ($dutchByEnglishValues as $constant => $value)
        {
            $constant = $isOnlyCapitalizeFirstLetterKey ? ucfirst(strtolower($constant)) : $constant;
            $value = $isOnlyCapitalizeFirstLetterValue ? ucfirst(strtolower($value)) : $value;

            $valuesString = $valuesString . $prefix . "('" . $constant . "','" . $value ."')";
            $prefix = ',';
        }

        return $valuesString;
    }


    /**
     * @param string $dateString
     * @return string
     */
    public static function castAsTimeStamp($dateString)
    {
        return "CAST('".$dateString."' AS TIMESTAMP)";
    }


    /**
     * @return string
     */
    public static function breedTypeTranslationValues()
    {
        return SqlUtil::createSqlValuesString(BreedTypeDutch::getConstants(), false, true);
    }


    /**
     * @return string
     */
    public static function breedTypeFirstLetterOnlyTranslationValues()
    {
        $translatedFirstLettersByOriginalBreedType = [];
        foreach (BreedTypeDutch::getConstants() as $key => $value) {
            $translatedFirstLettersByOriginalBreedType[$key] = StringUtil::getCapitalizedFirstLetter($value);
        }

        return SqlUtil::createSqlValuesString($translatedFirstLettersByOriginalBreedType, false, true);
    }


    /**
     * @return string
     */
    public static function genderTranslationValues()
    {
        return SqlUtil::createSqlValuesString(DutchGender::getConstants(), true, true);
    }


    /**
     * @param bool $isInformal
     * @return string
     */
    public static function declareIRTranslationValues($isInformal = true)
    {
        $values = $isInformal ? RequestTypeIRDutchInformal::getConstants() : RequestTypeIRDutchOfficial::getConstants();
        return SqlUtil::createSqlValuesString($values, false, false);
    }


    /**
     * @param int|string|array $ids
     * @return string
     * @throws \Exception
     */
    public static function getIdsFilterListString($ids)
    {
        if (is_int($ids) || ctype_digit($ids)) {
            $ids = [$ids];
        } elseif (!is_array($ids)) {
            throw new \Exception('Input should be an integer, integer string or array of integers');
        }

        if (count($ids) === 0) {
            throw new \Exception('Input is missing');
        }

        return implode(',', $ids);
    }


    /**
     * @param array $values
     * @param bool $quoteIndividualValues
     * @return string
     */
    public static function getFilterListString($values, $quoteIndividualValues = false)
    {
        $glue = $quoteIndividualValues ? "','" : ',';
        $prefixAndSuffix = $quoteIndividualValues ? "'" : '';

        return $prefixAndSuffix . implode($glue, $values) . $prefixAndSuffix;
    }


    /**
     * @param Connection $conn
     * @param $animalIds
     * @param bool $sortResults
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function getMissingAnimalIds(Connection $conn, $animalIds, $sortResults = false)
    {
        if (count($animalIds) === 0) { return []; }

        $wrappedAnimalIds = SqlUtil::valueString($animalIds, false);

        $sql = "SELECT
                  missing.animal_id
                FROM
                  (VALUES $wrappedAnimalIds) AS missing(animal_id)
                  LEFT JOIN animal a ON a.id = missing.animal_id
                WHERE a.id ISNULL";
        $missingAnimalIds = $conn->query($sql)->fetchAll();

        return SqlUtil::getSingleValueGroupedSqlResults('animal_id', $missingAnimalIds,true, $sortResults);
    }


    /**
     * @param TranslatorInterface $translator
     * @return string
     */
    public static function getGenderLetterTranslationValues(TranslatorInterface $translator)
    {
        $translations = [
            GenderType::NEUTER => $translator->trans(ReportServiceWithBreedValuesBase::NEUTER_SINGLE_CHAR),
            GenderType::FEMALE => $translator->trans(ReportServiceWithBreedValuesBase::FEMALE_SINGLE_CHAR),
            GenderType::MALE => $translator->trans(ReportServiceWithBreedValuesBase::MALE_SINGLE_CHAR),
        ];

        return SqlUtil::createSqlValuesString($translations);
    }


    /**
     * @param string|\DateTime $referenceDate
     * @param string $animalIdParentLabel
     * @param string|null $locationId
     * @return string
     */
    public static function animalResidenceSqlJoin($referenceDate, $animalIdParentLabel = 'a.id', $locationId = null)
    {
        $animalIdParentLabel = strtr($animalIdParentLabel, [' ' => '']);
        if (is_string($referenceDate)) {
            $referenceDate = (new \DateTime($referenceDate));
        }
        $referenceDateString = $referenceDate->format('Y-m-d');
        $locationFilter = is_int($locationId) ? 'AND location_id = '.$locationId : '';

        return "LEFT JOIN (
                          SELECT
                            r.animal_id,
                            1 as priority
                          FROM (
                                 SELECT
                                   r.animal_id,
                                   max(id) as max_id
                                 FROM animal_residence r
                                 WHERE
                                   start_date NOTNULL AND end_date NOTNULL AND
                                   DATE(start_date) <= '$referenceDateString' AND DATE(end_date) >= '$referenceDateString'
                                   AND is_pending = FALSE
                                   $locationFilter
                                 GROUP BY r.animal_id
                               )closed_residence
                            INNER JOIN animal_residence r ON r.id = closed_residence.max_id
                        )closed_residence ON closed_residence.animal_id = $animalIdParentLabel
              LEFT JOIN (
                          SELECT
                            open_residence.animal_id,
                            2 as priority
                          FROM (
                                 SELECT
                                   open_residence.animal_id,
                                   open_residence.max_start_date,
                                   max(id) as max_id
                                 FROM (
                                        SELECT
                                          r.animal_id,
                                          max(start_date) as max_start_date
                                        FROM animal_residence r
                                        WHERE
                                          start_date NOTNULL AND end_date ISNULL AND
                                          DATE(start_date) <= '$referenceDateString'
                                          AND is_pending = FALSE
                                          $locationFilter
                                        GROUP BY animal_id
                                      )open_residence
                                   INNER JOIN animal_residence r ON r.animal_id = open_residence.animal_id AND r.start_date = open_residence.max_start_date
                                 GROUP BY open_residence.animal_id, open_residence.max_start_date
                               )open_residence
                            INNER JOIN animal_residence r ON r.id = open_residence.max_id
                        )open_residence ON open_residence.animal_id = $animalIdParentLabel";
    }


    /**
     * @return string
     */
    public static function animalResidenceWhereCondition()
    {
        return ' (open_residence.animal_id NOTNULL OR closed_residence.animal_id NOTNULL) ';
    }
}