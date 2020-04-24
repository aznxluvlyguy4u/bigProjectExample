<?php

namespace AppBundle\Entity;

use AppBundle\Util\SqlUtil;
use Doctrine\Common\Collections\Criteria;

/**
 * Class InbreedingCoefficientTaskSmallRepository
 * @package AppBundle\Entity
 */
class InbreedingCoefficientTaskSmallRepository extends BaseRepository {

    /**
     * @param  int  $ramId
     * @param  int  $eweId
     */
    function add(int $ramId, int $eweId)
    {
        $task = new InbreedingCoefficientTaskSmall(
            $ramId, $eweId
        );
        $this->getManager()->persist($task);
        $this->getManager()->flush();
    }


    function next(): ?InbreedingCoefficientTaskReport
    {
        return $this->findOneBy([],['id' => Criteria::ASC]);
    }


    function bumpSequence()
    {
        SqlUtil::bumpPrimaryKeySeq($this->getConnection(), InbreedingCoefficientTaskSmall::getTableName());
    }
}
