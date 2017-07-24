<?php


namespace AppBundle\Migration;


use AppBundle\Component\MessageBuilderBase;
use AppBundle\Enumerator\QueryType;
use AppBundle\Enumerator\TagStateType;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use AppBundle\Util\StringUtil;
use AppBundle\Util\TimeUtil;
use Com\Tecnick\Color\Exception;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class TagReplaceMigrator
 */
class TagReplaceMigrator extends MigratorBase
{
    const BATCH = 1000;
    const TAG_INSERT = 'TAG_INSERT';
    const ULN_HISTORY_INSERT = 'ULN_HISTORY_INSERT';


    /**
     * TagReplaceMigrator constructor.
     * @param CommandUtil $cmdUtil
     * @param ObjectManager $em
     * @param array $data
     * @param int $developerId
     */
    public function __construct(CommandUtil $cmdUtil, ObjectManager $em, array $data, $developerId)
    {
        parent::__construct($cmdUtil, $em, $cmdUtil->getOutputInterface(), $data, null, $developerId);
    }

    public function migrate()
    {
        DoctrineUtil::updateTableSequence($this->conn, ['declare_base', 'tag']);
        
        $animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $sql = "SELECT
                CONCAT(uln_country_code_to_replace,' ',uln_number_to_replace,'|',uln_country_code_replacement,' ',uln_number_replacement) as uln_set, replace_date 
                FROM declare_tag_replace";

        $currentTagReplacements = SqlUtil::groupSqlResultsOfKey1ByKey2(
            'replace_date', 'uln_set', $this->conn->query($sql)->fetchAll());

        /*
         * NOTE! The batches are processed alphabetically. So make sure the base insert batch starts first alphabetically.
         * And ulnHistory comes after tag insert,
         */
        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
            ->createBatchSet(self::TAG_INSERT)
            ->createBatchSet(self::ULN_HISTORY_INSERT)
        ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);
        $tagBatchSet = $this->sqlBatchProcessor->getSet(self::TAG_INSERT);
        $ulnHistoryBatchSet = $this->sqlBatchProcessor->getSet(self::ULN_HISTORY_INSERT);

        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO declare_base (id, log_date, request_id, message_id, request_state, 
                                      action, recovery_indicator, relation_number_keeper, ubn, type, action_by_id) VALUES ");

        $insertBatchSet->setSqlQueryBase("INSERT INTO declare_tag_replace (id, animal_id, animal_type, replace_date,
                                 uln_country_code_to_replace, uln_number_to_replace, animal_order_number_to_replace, 
                                 uln_country_code_replacement, uln_number_replacement, animal_order_number_replacement)
                    VALUES ");

        $tagBatchSet->setSqlQueryBase('INSERT INTO tag (id, animal_id, tag_status, animal_order_number, order_date, uln_country_code, uln_number) VALUES ');

        $ulnHistoryBatchSet->setSqlQueryBase("INSERT INTO ulns_history (tag_id, animal_id) VALUES ");

        $this->sqlBatchProcessor->start(count($this->data));

        $maxDeclareBaseId = SqlUtil::getMaxId($this->conn, 'declare_base');
        $maxTagId = SqlUtil::getMaxId($this->conn, 'tag');
        $firstMaxTagId = $maxTagId+1;
        $animalIdNullValue = 'NULL';
        $logDate = TimeUtil::getLogDateString();

        try {

            foreach ($this->data as $record) {

                $ulnOld = $record[2];
                $ulnNew = $record[3];

                $ulnOldParts = explode(' ', $ulnOld);
                $ulnNewParts = explode(' ', $ulnNew);

                if (count($ulnOldParts) != 2 || count($ulnNewParts) != 2) {
                    $baseInsertBatchSet->incrementSkippedCount();
                    $insertBatchSet->incrementSkippedCount();
                    $tagBatchSet->incrementSkippedCount();
                    $ulnHistoryBatchSet->incrementSkippedCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                $ulnSet = $ulnOld . '|' . $ulnNew;

                if (key_exists($ulnSet, $currentTagReplacements)) {
                    $baseInsertBatchSet->incrementAlreadyDoneCount();
                    $insertBatchSet->incrementAlreadyDoneCount();
                    $tagBatchSet->incrementAlreadyDoneCount();
                    $ulnHistoryBatchSet->incrementAlreadyDoneCount();
                    $this->sqlBatchProcessor->advanceProgressBar();
                    continue;
                }

                $vsmId = $record[0];
                $animalId = ArrayUtil::get($vsmId, $animalIdsByVsmId, $animalIdNullValue);

                //ReplaceDate cannot be null
                $replaceDateString = TimeUtil::getTimeStampForSqlFromAnyDateString($record[1]);
                if($replaceDateString == null) {
                    $replaceDateString = TimeUtil::getTimeStampForSqlFromAnyDateString(self::BLANK_DATE_FILLER);
                }
                $replaceDateString = SqlUtil::getNullCheckedValueForSqlQuery($replaceDateString, true);


                $ulnCountryCodeOld = $ulnOldParts[0];
                $ulnNumberOld = $ulnOldParts[1];
                $animalOrderNumberOld = StringUtil::getLast5CharactersFromString($ulnNumberOld);

                $ulnCountryCodeNew = $ulnNewParts[0];
                $ulnNumberNew = $ulnNewParts[1];
                $animalOrderNumberNew = StringUtil::getLast5CharactersFromString($ulnNumberNew);

                $requestId = MessageBuilderBase::getNewRequestId();
                $baseInsertBatchSet->appendValuesString("(".++$maxDeclareBaseId.",'".$logDate."','".$requestId."','".$requestId."','IMPORTED','V','N','IMPORTED','IMPORTED','DeclareTagReplace',2151)");
                $baseInsertBatchSet->incrementBatchCount();

                $insertBatchSet->appendValuesString("(".$maxDeclareBaseId.','. $animalId . ",3," . $replaceDateString . ",'"
                    . $ulnCountryCodeOld . "','" . $ulnNumberOld . "','" . $animalOrderNumberOld . "','"
                    . $ulnCountryCodeNew . "','" . $ulnNumberNew . "','" . $animalOrderNumberNew . "')");
                $insertBatchSet->incrementBatchCount();

                if ($animalId !== $animalIdNullValue) {
                    $tagBatchSet->appendValuesString("(".++$maxTagId.",NULL,'".TagStateType::REPLACED."','".$animalOrderNumberOld."',". $replaceDateString.",'".$ulnCountryCodeOld."','".$ulnNumberOld."')");

                    $ulnHistoryBatchSet->appendValuesString("(".$maxTagId.",".$animalId.")");

                    $tagBatchSet->incrementBatchCount();
                    $ulnHistoryBatchSet->incrementBatchCount();
                }

                $this->sqlBatchProcessor
                    ->processAtBatchSize()
                    ->advanceProgressBar()
                ;
            }
            $this->sqlBatchProcessor->end();

        } catch (Exception $exception) {
            $sql = "DELETE FROM declare_base WHERE log_date = CAST('".$logDate."' AS TIMESTAMP) 
            AND ubn = 'IMPORTED' AND type = 'DeclareTagReplace'";
            $this->conn->exec($sql);

            throw new Exception($exception);

        } finally {
            DoctrineUtil::updateTableSequence($this->conn, ['declare_base','tag']);
            $this->cmdUtil->writeln('First Max TagId inserted: '.$firstMaxTagId);
            $this->cmdUtil->writeln('Imported TagDeclares logDate: '.$logDate);
            $this->cmdUtil->printClosingLine();
        }
    }


}