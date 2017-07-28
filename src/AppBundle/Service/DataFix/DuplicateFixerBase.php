<?php

namespace AppBundle\Service\DataFix;

use AppBundle\Entity\Animal;
use AppBundle\Entity\AnimalRepository;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class DuplicateFixerBase
 */
class DuplicateFixerBase
{
    const SEARCH_KEY = 'search_key';
    const BATCH_SIZE = 1000;
    const VARIABLE_TYPE = 'variable_type';
    const TABLE_NAME = 'table_name';
    const IS_MERGE_SUCCESSFUL = 'is_merge_successful';
    const ARE_MEASUREMENTS_UPDATED = 'are_measurements_updated';


    /** @var ObjectManager|EntityManagerInterface $em */
    protected $em;
    /** @var AnimalRepository $animalRepository */
    protected $animalRepository;
    /** @var CommandUtil */
    protected $cmdUtil;
    /** @var Connection */
    protected $conn;

    /** @var array */
    protected $tableNames;

    /**
     * DuplicateAnimalsFixer constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        $this->conn = $this->em->getConnection();

        $this->animalRepository = $this->em->getRepository(Animal::class);
    }


    /**
     * @param CommandUtil $cmdUtil
     */
    public function setCmdUtil(CommandUtil $cmdUtil)
    {
        if ($this->cmdUtil === null) {
            $this->cmdUtil = $cmdUtil;
        }
    }


    /**
     * @return array
     */
    protected function getTableNames()
    {
        if ($this->tableNames === null || $this->tableNames === []) {
            $this->tableNames = DoctrineUtil::getTableNames($this->conn);
        }
        return $this->tableNames;
    }


    /**
     * @param $value
     * @param $columnName
     * @param array $tableNames
     * @param bool $valueBetweenQuotes
     */
    protected function deleteRecords($value, $columnName, array $tableNames, $valueBetweenQuotes = false)
    {
        $value = $valueBetweenQuotes ? "'".$value."'" : $value;
        foreach ($tableNames as $tableName)
        {
            $sql = "DELETE FROM $tableName WHERE $columnName = ".$value;
            $this->conn->exec($sql);
        }
    }


    /**
     * @param $primaryValue
     * @param $secondaryValue
     * @param array $tableNamesByVariableType
     * @return array
     */
    protected function mergeColumnValuesInTables($primaryValue, $secondaryValue, array $tableNamesByVariableType)
    {
        $defaultResult = [
            self::IS_MERGE_SUCCESSFUL => false,
            self::ARE_MEASUREMENTS_UPDATED => false,
        ];

        if((!is_int($primaryValue) && !ctype_digit($primaryValue)) ||
            (!is_int($secondaryValue) && !ctype_digit($secondaryValue))) { return $defaultResult; }

        if(!is_array($tableNamesByVariableType) || count($tableNamesByVariableType) === 0) { return $defaultResult; }

        //Check in which tables have the $secondaryValue

        $sql = '';
        $counter = 0;
        foreach ($tableNamesByVariableType as $tableNameByVariableType) {

            $counter++;
            $tableName = $tableNameByVariableType[self::TABLE_NAME];
            $variableType = $tableNameByVariableType[self::VARIABLE_TYPE];

            if(!in_array($tableName, $this->getTableNames(), true)) {
                continue;
            }

            $sql = $sql."SELECT ".$counter." as count, '".$tableName."' as ".self::TABLE_NAME.", '".$variableType.
                "' as ".self::VARIABLE_TYPE." FROM ".$tableName." WHERE ".$variableType." = ".$secondaryValue." UNION ";
        }
        $sql = rtrim($sql, 'UNION ');
        $results = $this->conn->query($sql)->fetchAll();

        $secondaryAnimalIsInAnyTable = count($results) != 0;

        $anyMeasurementsUpdated = false;

        $sqlUpdateQueries = [];
        if($secondaryAnimalIsInAnyTable) {
            foreach ($results as $result) {
                $tableName = $result[self::TABLE_NAME];
                $variableType = $result[self::VARIABLE_TYPE];

                $uniqueUpdateKey = $tableName.'-'.$variableType;
                if(!array_key_exists($uniqueUpdateKey, $sqlUpdateQueries)) {
                    $sql = "UPDATE ".$tableName." SET ".$variableType." = ".$primaryValue." WHERE ".$variableType." = ".$secondaryValue;
                    $sqlUpdateQueries[$uniqueUpdateKey] = $sql;
                }

                if($tableName == 'exterior' || $tableName == 'body_fat' || $tableName == 'weight'
                    || $tableName == 'muscle_thickness' || $tableName == 'tail_length') {
                    $anyMeasurementsUpdated = true;
                }
            }
        }

        //Execute updates
        foreach ($sqlUpdateQueries as $sqlUpdateQuery) {
            $this->conn->exec($sqlUpdateQuery);
        }

        return [
            self::IS_MERGE_SUCCESSFUL => true,
            self::ARE_MEASUREMENTS_UPDATED => $anyMeasurementsUpdated,
        ];
    }
}