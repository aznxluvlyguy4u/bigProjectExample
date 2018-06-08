<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewLocationDetails;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewLocationDetailsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewLocationDetails::class);
        $this->setTableName(SqlView::VIEW_LOCATION_DETAILS);
        $this->setPrimaryKeyName(ViewLocationDetails::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $locationIds
     * @return ArrayCollection|ViewLocationDetails[]
     * @throws \Exception
     */
    public function findByLocationIds($locationIds = [])
    {
        return $this->findByPrimaryIds($locationIds);
    }


    /**
     * @param int $locationId
     * @return ViewLocationDetails
     * @throws \Exception
     */
    public function findOneByLocationId($locationId)
    {
        return $this->findOneByPrimaryId($locationId);
    }
}