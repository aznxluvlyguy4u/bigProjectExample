<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\ProcessLogType;
use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;
use \DateTime as DateTime;

/**
 * Class ProcessLogRepository
 * @package AppBundle\Entity
 */
class ProcessLogRepository extends BaseRepository {

    /**
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    function deactivateBreedValuesResultTableUpdaterProcessLog(): int {
        $sql = "UPDATE process_log SET is_active = FALSE WHERE is_active = TRUE 
                                           AND type_id = ".ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER;
        return SqlUtil::updateWithCount($this->getConnection(), $sql);
    }

    /**
     * @param string $breedValueTypeResultTableValue
     * @param string $generationDate
     * @return ProcessLog
     * @throws \Doctrine\DBAL\DBALException
     */
    function startBreedValuesResultTableUpdaterProcessLog(string $breedValueTypeResultTableValue, string $generationDate): ProcessLog {
        $breedValueTypeId = $this->getBreedValueTypeId($breedValueTypeResultTableValue);
        $processLog = new ProcessLog();
        $processLog
            ->setTypeId(ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER)
            ->setType(ProcessLogType::getName(ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER))
            ->setCategory($breedValueTypeResultTableValue)
            ->setCategoryId($breedValueTypeId)
            ->setSubCategory($generationDate)
            ;
        $this->getManager()->persist($processLog);
        $this->getManager()->flush();
        return $processLog;
    }


    /**
     * @param ProcessLog $processLog
     * @return ProcessLog
     * @throws \Exception
     */
    function endProcessLog(ProcessLog $processLog): ProcessLog
    {
        $processLog->setEndDate(new DateTime());
        $this->getManager()->persist($processLog);
        $this->getManager()->flush();
        return $processLog;
    }


    /**
     * @param string $breedValueTypeResultTableValue
     * @param string $generationDate
     * @param bool $mustBeFinished
     * @return ProcessLog|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    function findBreedValuesResultTableUpdaterProcessLog(string $breedValueTypeResultTableValue,
                                                         string $generationDate, bool $mustBeFinished) {
        $breedValueTypeId = $this->getBreedValueTypeId($breedValueTypeResultTableValue);

        $qb = $this->getManager()->createQueryBuilder();
        $queryBuilder =
            $qb
                ->select('p')
                ->from(ProcessLog::class, 'p')
                ->where($qb->expr()->eq('p.typeId', ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER))
                ->andWhere($qb->expr()->eq('p.categoryId', $breedValueTypeId))
                ->andWhere($qb->expr()->eq('p.subCategory', "'".$generationDate."'"))
                ->andWhere($qb->expr()->eq('p.isActive', 'true'))
            ;

        if ($mustBeFinished) {
            $qb->andWhere($qb->expr()->isNotNull('p.endDate'));
        }

        $qb->orderBy('p.startDate', Criteria::DESC)
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery();

        try {
            return $query->getSingleResult();
        } catch (NoResultException $exception) {
            return null;
        }
    }


    /**
     * @param string $breedValueNl
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getBreedValueTypeId($breedValueTypeResultTableValue): int {
        $sql = "SELECT id  FROM breed_value_type WHERE result_table_value_variable = '$breedValueTypeResultTableValue'";
        $id = $this->getConnection()->query($sql)->fetch()['id'];
        if (!is_int($id)) {
            throw new \Exception("invalid breedValueNl: $breedValueTypeResultTableValue");
        }
        return $id;
    }
}