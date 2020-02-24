<?php


namespace AppBundle\SqlView\Repository;


use AppBundle\SqlView\View\ViewAnimalIsPublicDetails;
use AppBundle\Util\SqlView;
use Doctrine\Common\Collections\ArrayCollection;

class ViewAnimalIsPublicDetailsRepository extends SqlViewRepositoryBase implements SqlViewRepositoryInterface
{
    public function init()
    {
        $this->setClazz(ViewAnimalIsPublicDetails::class);
        $this->setTableName(SqlView::VIEW_ANIMAL_IS_PUBLIC);
        $this->setPrimaryKeyName(ViewAnimalIsPublicDetails::getPrimaryKeyName());
    }


    /**
     * @param array|int[] $animalIds
     * @return ArrayCollection|ViewAnimalIsPublicDetails[]
     * @throws \Exception
     */
    public function findByAnimalIds($animalIds = [])
    {
        $retrievedDetails = $this->findByPrimaryIds($animalIds);
        foreach ($animalIds as $animalId) {
            if (!$retrievedDetails->contains($animalId)) {
                $retrievedDetails->set($animalId, new ViewAnimalIsPublicDetails($animalId));
            }
        }

        return $retrievedDetails;
    }


    /**
     * @param int $animalId
     * @return ViewAnimalIsPublicDetails
     * @throws \Exception
     */
    public function findOneByAnimalId($animalId)
    {
        $retrievedDetails = $this->findOneByPrimaryId($animalId);
        return $retrievedDetails != null ? $retrievedDetails : new ViewAnimalIsPublicDetails($animalId);
    }


    /**
     * @param array $ulns
     * @return ArrayCollection|ViewAnimalIsPublicDetails[]
     * @throws \Exception
     */
    public function findByUlns(array $ulns = [])
    {
        return $this->findByUlnsBase($ulns);
    }
}
