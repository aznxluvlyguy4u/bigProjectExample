<?php

namespace AppBundle\Entity;
use AppBundle\Enumerator\UpdateType;
use AppBundle\Service\TaskService;
use AppBundle\Util\ArrayUtil;
use AppBundle\Util\DateUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class UpdateAnimalDataWorkerRepository
 * @package AppBundle\Entity
 */
class UpdateAnimalDataWorkerRepository extends BaseRepository {

    /**
     * @param Person $user
     * @param int|null $limit
     * @return array|mixed
     * @throws \Exception
     */
    function getTasks(Person $user, ?int $limit = null)
    {
        if (!$user) {
            return [];
        }


        if ($limit && $limit < 1) {
            return [];
        }

        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(UpdateAnimalDataWorker::class, 'w')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->gte('w.startedAt', DateUtil::getQueryBuilderFormat(TaskService::getMaxNonExpiredDate())),
                    $qb->expr()->isNotNull('w.finishedAt')
                )
            )
            ->orWhere(
                $qb->expr()->isNull('w.finishedAt')
            )
            ->orderBy('w.startedAt', Criteria::DESC)
            ->setMaxResults($limit)
        ;

        $results = $qb->getQuery()->getResult();

        // Add inbreeding coefficient

        /** @var InbreedingCoefficientProcess $process */
        $process = $this->getManager()->getRepository(InbreedingCoefficientProcess::class)->getAdminProcess();
        if ($process) {
            $results[] = (new UpdateAnimalDataWorker())
                ->setUpdateType(
                    $process->isRecalculate() ?
                        UpdateType::INBREEDING_COEFFICIENT_RECALCULATION : UpdateType::INBREEDING_COEFFICIENT_CALCULATION
                )
                ->setStartedAt($process->getStartedAt())
                ->setFinishedAt($process->getFinishedAt())
                ->setHash("Klaar over (inschatting): " . $process->estimatedTimeOfArrival()
                    . ' - voortgang('.$process->getProgress().'%) - '.$process->getProcessed().'/'.$process->getTotal())
                ->setErrorCode($process->getErrorCode())
                ->setErrorMessage($process->getErrorMessage())
                ->setDebugErrorMessage($process->getDebugErrorMessage())
                ;
        }

        usort($results, function (UpdateAnimalDataWorker $a, UpdateAnimalDataWorker $b) {
            $pos_a = $a->getStartedAt()->getTimestamp();
            $pos_b = $b->getStartedAt()->getTimestamp();
            return $pos_a - $pos_b;
        });

        return $results;
    }


    /**
     * @param string $hash
     * @return bool
     * @throws \Exception
     */
    function isSimilarNonExpiredTaskAlreadyInProgress($hash)
    {
        $qb = $this->getManager()->createQueryBuilder();
        $qb->select('w')
            ->from(UpdateAnimalDataWorker::class, 'w')
            ->where($qb->expr()->eq('w.hash', "'".$hash."'"))
            ->andWhere($qb->expr()->isNull('w.finishedAt'))
        ;

        $workerInProgress = $qb->getQuery()->getResult();

        return !empty($workerInProgress);
    }
}
