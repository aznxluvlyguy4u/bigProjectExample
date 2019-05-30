<?php

namespace AppBundle\Entity;

use AppBundle\Enumerator\ProcessLogType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;
use \DateTime as DateTime;

/**
 * Class ProcessLogRepository
 * @package AppBundle\Entity
 */
class ProcessLogRepository extends BaseRepository {

    /**
     * @param string $breedValueTypeResultTableValue
     * @return ProcessLog
     * @throws \Doctrine\DBAL\DBALException
     */
    function startBreedValuesResultTableUpdaterProcessLog(string $breedValueTypeResultTableValue): ProcessLog {
        $breedValueTypeId = $this->getBreedValueTypeId($breedValueTypeResultTableValue);
        $processLog = new ProcessLog();
        $processLog
            ->setTypeId(ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER)
            ->setType(ProcessLogType::getName(ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER))
            ->setCategory($breedValueTypeResultTableValue)
            ->setCategoryId($breedValueTypeId)
            ;
        $this->getManager()->persist($processLog);
        $this->getManager()->flush();
        return $processLog;
    }


    /**
     * @param ProcessLog $processLog
     */
    function endProcessLog(ProcessLog $processLog)
    {
        $processLog->setEndDate(new DateTime());
        $this->getManager()->persist($processLog);
        $this->getManager()->flush();
    }


    /**
     * @param string $breedValueTypeResultTableValue
     * @param bool $mustBeFinished
     * @return ProcessLog|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    function findBreedValuesResultTableUpdaterProcessLog(string $breedValueTypeResultTableValue, bool $mustBeFinished) {
        $breedValueTypeId = $this->getBreedValueTypeId($breedValueTypeResultTableValue);

        $qb = $this->getManager()->createQueryBuilder();
        $queryBuilder =
            $qb
                ->select('p')
                ->from(ProcessLog::class, 'p')
                ->where($qb->expr()->eq('p.typeId', ProcessLogType::BREED_VALUES_RESULT_TABLE_UPDATER))
                ->andWhere($qb->expr()->eq('p.categoryId', $breedValueTypeId))
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