<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewAnimalLivestockOverviewDetails;
use AppBundle\SqlView\View\ViewScanMeasurements;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewScanMeasurementsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewScanMeasurements::class);
        $this->setTableName(SqlView::VIEW_SCAN_MEASUREMENTS);
        $this->setPrimaryKeyName(ViewScanMeasurements::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $animalIds
     * @return ArrayCollection|ViewAnimalLivestockOverviewDetails[]
     * @throws \Exception
     */
    public function findByAnimalIds($animalIds = [])
    {
        return $this->findByPrimaryIds($animalIds);
    }


    /**
     * @param int $animalId
     * @return ViewAnimalLivestockOverviewDetails
     * @throws \Exception
     */
    public function findOneByAnimalId($animalId)
    {
        return $this->findOneByPrimaryId($animalId);
    }
}