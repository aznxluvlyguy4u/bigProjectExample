<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewMinimalParentDetails;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewMinimalParentDetailsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewMinimalParentDetails::class);
        $this->setTableName(SqlView::VIEW_MINIMAL_PARENT_DETAILS);
        $this->setPrimaryKeyName(ViewMinimalParentDetails::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $animalIds
     * @return ArrayCollection|ViewMinimalParentDetails[]
     * @throws \Exception
     */
    public function findByAnimalIds($animalIds = [])
    {
        return $this->findByPrimaryIds($animalIds);
    }


    /**
     * @param int $animalId
     * @return ViewMinimalParentDetails
     * @throws \Exception
     */
    public function findOneByAnimalId($animalId)
    {
        return $this->findOneByPrimaryId($animalId);
    }


    /**
     * @param array $ulns
     * @return ArrayCollection|ViewMinimalParentDetails[]
     * @throws \Exception
     */
    public function findByUlns(array $ulns = [])
    {
        return $this->findByUlnsBase($ulns);
    }
}
