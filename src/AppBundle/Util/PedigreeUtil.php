<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;

class PedigreeUtil
{
    const MAX_GENERATION = 3;

    /** @var ObjectManager */
    private $em;

    /** @var array */
    private $parentIds;

    public function __construct(ObjectManager $em, $animalId)
    {
        $this->em = $em;
        $this->parentIds = [];
        $this->findParents($animalId);
    }


    /**
     * @param int $animalId
     * @param int $generation
     */
    private function findParents($animalId, $generation = 1)
    {
        $sql = "SELECT parent_father_id, parent_mother_id FROM animal WHERE id = ".$animalId;
        $parentIds = $this->em->getConnection()->query($sql)->fetch();

        $generation++;

        foreach ($parentIds as $parentId) {
            if(is_int($parentId)) {
                $this->parentIds[] = $parentId;

                if($generation <= self::MAX_GENERATION) {
                    $this->findParents($parentId, $generation);
                }
            }
        }
    }


    /**
     * @return array
     */
    public function getParentIds()
    {
        return $this->parentIds;
    }

}