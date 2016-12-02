<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\OutputInterface;

class SqlUtil
{
    const NULL = 'NULL';
    const DATE_FORMAT = 'Y-m-d H:i:s';

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


    public static function importFromCsv()
    {
        //TODO
    }
}