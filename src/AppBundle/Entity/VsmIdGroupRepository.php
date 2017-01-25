<?php

namespace AppBundle\Entity;

use AppBundle\Util\CommandUtil;
use AppBundle\Util\SqlUtil;

class VsmIdGroupRepository extends BaseRepository {

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getPrimaryVsmIdsBySecondaryVsmId()
    {
        $sql = "SELECT primary_vsm_id, secondary_vsm_id FROM vsm_id_group";
        $results = $this->getConnection()->query($sql)->fetchAll();

        $searchArray = [];
        foreach ($results as $result) {
            $searchArray[$result['secondary_vsm_id']] = $result['primary_vsm_id'];
        }

        return $searchArray;
    }


    /**
     * @param CommandUtil $cmdUtil
     * @throws \Doctrine\DBAL\DBALException
     */
    public function fixSwappedPrimaryAndSecondaryVsmId(CommandUtil $cmdUtil)
    {
        $sql = "SELECT v.id FROM vsm_id_group v
                  LEFT JOIN animal a ON a.name = v.primary_vsm_id
                  LEFT JOIN animal b ON b.name = v.secondary_vsm_id
                WHERE a.id ISNULL AND b.id NOTNULL";
        $results = $this->getConnection()->query($sql)->fetchAll();
        $primaryAndSecondaryInvertedVsmIdGroupIds = SqlUtil::getSingleValueGroupedSqlResults('id', $results);

        $count = count($primaryAndSecondaryInvertedVsmIdGroupIds);
        if($count == 0) {
            if($cmdUtil != null) { $cmdUtil->writeln('There are no inverted vsmIdGroup ids!'); }
            return;
        }

        if($cmdUtil != null) { $cmdUtil->printTitle('Fixing inverted vsmIdGroup ids'); }

        $deletedCount = 0;
        $updatedCount = 0;
        if($cmdUtil != null) { $cmdUtil->setStartTimeAndPrintIt($count, 1); }
        foreach ($primaryAndSecondaryInvertedVsmIdGroupIds as $vsmIdGroupId) {
            $sql = "SELECT primary_vsm_id, secondary_vsm_id FROM vsm_id_group WHERE id = ".$vsmIdGroupId;
            $result = $this->getConnection()->query($sql)->fetch();

            $primaryVsmId = $result['primary_vsm_id'];
            $secondaryVsmId = $result['secondary_vsm_id'];

            $sql = "SELECT COUNT(id) FROM vsm_id_group WHERE primary_vsm_id = '".$secondaryVsmId."' AND secondary_vsm_id = '".$primaryVsmId."'";
            $count = $this->getConnection()->query($sql)->fetch()['count'];
            if($count > 0) {
                //Record already exists so delete it
                $this->getConnection()->exec('DELETE FROM vsm_id_group WHERE id = '.$vsmIdGroupId);
                $deletedCount++;
            } else {
                //Check if secondaryVsmId already exists
                $sql = "SELECT * FROM vsm_id_group WHERE secondary_vsm_id = '".$primaryVsmId."'";
                $currentGroups = $this->getConnection()->query($sql)->fetchAll();

                $currentGroupsCount = count($currentGroups);
                if($currentGroupsCount > 0) {
                    foreach ($currentGroups as $currentGroup) {
                        $currentPrimaryVsmId = $currentGroup['primary_vsm_id'];
                        $currentSecondaryVsmId = $currentGroup['secondary_vsm_id'];
                        $currentVsmIdGroupId = $currentGroup['id'];

                        if($primaryVsmId == $currentSecondaryVsmId && $secondaryVsmId == $currentPrimaryVsmId) {
                            //Record already exists so delete it
                            $this->getConnection()->exec('DELETE FROM vsm_id_group WHERE id = '.$vsmIdGroupId);
                            $deletedCount++;
                        } else {
                            //The new vsmId pair gotten from the first query has a guaranteed animal
                            //So delete the old pair and insert a new one
                            $this->getConnection()->exec('DELETE FROM vsm_id_group WHERE id = '.$currentVsmIdGroupId);
                            $deletedCount++;

                            $sql = "UPDATE vsm_id_group SET primary_vsm_id = '".$secondaryVsmId."', secondary_vsm_id = '".$primaryVsmId."' WHERE id =  ".$vsmIdGroupId;
                            $this->getConnection()->exec($sql);
                            $updatedCount++;
                        }
                    }
                } else {
                    $sql = "UPDATE vsm_id_group SET primary_vsm_id = '".$secondaryVsmId."', secondary_vsm_id = '".$primaryVsmId."' WHERE id =  ".$vsmIdGroupId;
                    $this->getConnection()->exec($sql);
                    $updatedCount++;
                }
            }
            if($cmdUtil != null) { $cmdUtil->advanceProgressBar(1, 'Updated|Deleted: '.$updatedCount.'|'.$deletedCount); }
        }
        if($cmdUtil != null) { $cmdUtil->setEndTimeAndPrintFinalOverview(); }
    }

}
