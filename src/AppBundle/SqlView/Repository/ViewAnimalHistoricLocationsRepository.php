<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewAnimalHistoricLocations;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewAnimalHistoricLocationsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewAnimalHistoricLocations::class);
        $this->setTableName(SqlView::VIEW_ANIMAL_HISTORIC_LOCATIONS);
        $this->setPrimaryKeyName(ViewAnimalHistoricLocations::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $animalIds
     * @return ArrayCollection|ViewAnimalHistoricLocations[]
     * @throws \Exception
     */
    public function findByAnimalIds($animalIds = [])
    {
        $retrievedDetails = $this->findByPrimaryIds($animalIds);
        foreach ($animalIds as $animalId) {
            if (!$retrievedDetails->contains($animalId)) {
                $retrievedDetails->set($animalId, new ViewAnimalHistoricLocations($animalId));
            }
        }
        return $retrievedDetails;
    }


    /**
     * @param int $animalId
     * @return ViewAnimalHistoricLocations
     * @throws \Exception
     */
    public function findOneByAnimalId($animalId)
    {
        $retrievedDetails = $this->findOneByPrimaryId($animalId);
        return $retrievedDetails != null ? $retrievedDetails : new ViewAnimalHistoricLocations($animalId);
    }


    /**
     * @param array $ulns
     * @return ArrayCollection|ViewAnimalHistoricLocations[]
     * @throws \Exception
     */
    public function findByUlns(array $ulns = [])
    {
        return $this->findByUlnsBase($ulns);
    }
}
