<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewLitterDetails;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewLitterDetailsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewLitterDetails::class);
        $this->setTableName(SqlView::VIEW_LITTER_DETAILS);
        $this->setPrimaryKeyName(ViewLitterDetails::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $litterIds
     * @return ArrayCollection|ViewLitterDetails[]
     * @throws \Exception
     */
    public function findByLitterIds($litterIds = [])
    {
        return $this->findByPrimaryIds($litterIds);
    }


    /**
     * @param int $litterId
     * @return ViewLitterDetails
     * @throws \Exception
     */
    public function findOneByLitterId($litterId)
    {
        return $this->findOneByPrimaryId($litterId);
    }
}