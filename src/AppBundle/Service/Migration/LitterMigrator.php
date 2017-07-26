<?php

namespace AppBundle\Service\Migration;

use AppBundle\Enumerator\QueryType;
use AppBundle\Util\CommandUtil;
use AppBundle\Util\DoctrineUtil;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LitterMigrator
 */
class LitterMigrator extends Migrator2017JunServiceBase implements IMigratorService
{
    /** @inheritdoc */
    public function __construct(ObjectManager $em, $rootDir)
    {
        parent::__construct($em, $rootDir);
    }

    /** @inheritDoc */
    function run(CommandUtil $cmdUtil)
    {
        parent::run($cmdUtil);

        DoctrineUtil::updateTableSequence($this->conn, ['declare_base_nsfo']);

        $animalIdsByVsmId = $this->animalRepository->getAnimalPrimaryKeysByVsmIdArray();

        $sql = "SELECT
                    CONCAT(mom.name,'__',DATE(l.litter_date)) as key,
                    mom.name AS vsm_id, DATE(l.litter_date) as litter_date, born_alive_count, stillborn_count, request_state
                FROM litter l
                  INNER JOIN animal mom ON mom.id = l.animal_mother_id
                  INNER JOIN declare_nsfo_base b ON b.id = l.id";
        $currentLittersByVsmIdAndLitterDate = SqlUtil::createGroupedSearchArrayFromSqlResults($this->conn->query($sql)->fetchAll(),'key');

        /*
         * NOTE! The batches are processed alphabetically. So make sure the base insert batch starts first alphabetically.
         */
        $this->sqlBatchProcessor
            ->purgeAllSets()
            ->createBatchSet(QueryType::BASE_INSERT)
            ->createBatchSet(QueryType::INSERT)
            ->createBatchSet(QueryType::UPDATE)
        ;

        $baseInsertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::BASE_INSERT);
        $insertBatchSet = $this->sqlBatchProcessor->getSet(QueryType::INSERT);
        $updateBatchSet = $this->sqlBatchProcessor->getSet(QueryType::UPDATE);

//        $baseInsertBatchSet->setSqlQueryBase("INSERT INTO declare_base_nsfo (id, log_date, request_id, message_id, request_state,
//                                      action, recovery_indicator, relation_number_keeper, ubn, type, action_by_id) VALUES ");
//
//        $insertBatchSet->setSqlQueryBase("INSERT INTO declare_tag_replace (id, animal_id, animal_type, replace_date,
//                                 uln_country_code_to_replace, uln_number_to_replace, animal_order_number_to_replace,
//                                 uln_country_code_replacement, uln_number_replacement, animal_order_number_replacement)
//                    VALUES ");
//
//        $updateBatchSet->setSqlQueryBase('INSERT INTO tag (id, animal_id, tag_status, animal_order_number, order_date, uln_country_code, uln_number) VALUES ');
    }
}