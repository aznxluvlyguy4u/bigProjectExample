<?php

namespace AppBundle\Service\DataFix;


use AppBundle\Enumerator\QueryType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use Doctrine\Common\Persistence\ObjectManager;


/**
 * Class UbnFixer
 */
class UbnFixer extends DuplicateFixerBase
{

    public function __construct(ObjectManager $em)
    {
        parent::__construct($em);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public function removeNonDigitsFromUbnOfBirthInAnimalMigrationTable(CommandUtil $cmdUtil)
    {
        return $this->removeNonDigitsFromUbnOfBirth('animal_migration_table', $cmdUtil);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int
     */
    public function removeNonDigitsFromUbnOfBirthInAnimalTable(CommandUtil $cmdUtil)
    {
        return $this->removeNonDigitsFromUbnOfBirth('animal', $cmdUtil);
    }


    /**
     * @param string $tableName
     * @param CommandUtil $cmdUtil
     * @return int
     */
    private function removeNonDigitsFromUbnOfBirth($tableName, CommandUtil $cmdUtil)
    {
        $cmdUtil->writeln('Removing non-digits from ubn_of_birth in '.$tableName.' table ...');

        $sql = "SELECT
                  id,
                  ubn_of_birth,
                  regexp_matches(ubn_of_birth, '\D') as regex --non digits
                FROM $tableName";
        $results = $this->conn->query($sql)->fetchAll();

        $this->setCmdUtil($cmdUtil);
        $updateBatchSet = $this->getSqlBatchProcessor()
            ->createBatchSet(QueryType::UPDATE)
            ->getSet(QueryType::UPDATE)
        ;

        $updateBatchSet->setSqlQueryBase("UPDATE $tableName 
                                            SET ubn_of_birth = v.ubn_of_birth
                                            FROM ( VALUES ");
        $updateBatchSet->setSqlQueryBaseEnd(") AS v(id, ubn_of_birth, old_ubn_of_birth) 
                               WHERE $tableName.id = v.id
                               AND $tableName.ubn_of_birth = v.old_ubn_of_birth");

        $totalCount = count($results);
        $this->getSqlBatchProcessor()->start($totalCount);

        foreach ($results as $result)
        {
            $id = $result['id'];
            $oldUbnOfBirth = $result['ubn_of_birth'];

            $ubnOfBirth = StringUtil::stripNonNumericCharsAndConvertToInteger($oldUbnOfBirth);

            $ubnOfBirthForSql = $ubnOfBirth === null ? 'NULL' : "'".$ubnOfBirth."'";

            $updateBatchSet->appendValuesString("(".$id.",".$ubnOfBirthForSql.",'".$oldUbnOfBirth."')");
            $updateBatchSet->incrementBatchCount();
            $this->getSqlBatchProcessor()
                ->processAtBatchSize()
                ->advanceProgressBar()
            ;
        }

        $this->getSqlBatchProcessor()->end();
        $this->deleteSqlBatchProcessor();

        return $totalCount;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int|string
     */
    public function removeLeadingZeroesFromUbnOfBirthInAnimalTable(CommandUtil $cmdUtil)
    {
        return $this->removeLeadingZeroesFromUbnOfBirth('animal', $cmdUtil);
    }


    /**
     * @param CommandUtil $cmdUtil
     * @return int|string
     */
    public function removeLeadingZeroesFromUbnOfBirthInAnimalMigrationTable(CommandUtil $cmdUtil)
    {
        return $this->removeLeadingZeroesFromUbnOfBirth('animal_migration_table', $cmdUtil);
    }


    /**
     * @param $tableName
     * @param CommandUtil $cmdUtil
     * @return int|string
     */
    private function removeLeadingZeroesFromUbnOfBirth($tableName, CommandUtil $cmdUtil)
    {
        $this->setCmdUtil($cmdUtil);

        $this->writeLn('Remove leading zeroes from ubn_of_birth in animal table ...');

        $sql = "UPDATE animal SET ubn_of_birth = v.corrected_ubn_of_birth
                FROM (
                       SELECT
                         id,
                         ubn_of_birth as old_ubn_of_birth,
                         ltrim(ubn_of_birth, '0') as corrected_ubn_of_birth
                       FROM animal
                       WHERE substr(ubn_of_birth, 1, 1) = '0'
                     ) AS v(id, old_ubn_of_birth, corrected_ubn_of_birth)
                WHERE animal.id = v.id AND animal.ubn_of_birth = v.old_ubn_of_birth";
        $updateCount = SqlUtil::updateWithCount($this->conn, $sql);

        $updateCount = $updateCount === 0 ? 'No' : $updateCount;
        $this->writeLn($updateCount . ' records updated');

        return $updateCount;
    }



}