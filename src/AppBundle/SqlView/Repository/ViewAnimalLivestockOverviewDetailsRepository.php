<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewAnimalLivestockOverviewDetails;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewAnimalLivestockOverviewDetailsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewAnimalLivestockOverviewDetails::class);
        $this->setTableName(SqlView::VIEW_ANIMAL_LIVESTOCK_OVERVIEW_DETAILS);
        $this->setPrimaryKeyName(ViewAnimalLivestockOverviewDetails::getPrimaryKeyName());
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