<?php

namespace AppBundle\Entity;
use AppBundle\Util\SqlUtil;

/**
 * Class GenderHistoryItemRepository
 * @package AppBundle\Entity
 */
class GenderHistoryItemRepository extends BaseRepository
{
    /**
     * @param array $animalIds
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteByAnimalsIds($animalIds)
    {
        $animalIdFilterString = SqlUtil::getFilterStringByIdsArray($animalIds, 'animal_id');
        if($animalIdFilterString != '') {
            $sql = "DELETE FROM gender_history_item WHERE ".$animalIdFilterString;
            $this->getConnection()->exec($sql);
        }
    }
}