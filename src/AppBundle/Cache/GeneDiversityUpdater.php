<?php


namespace AppBundle\Cache;


use AppBundle\Util\SqlUtil;
use Doctrine\DBAL\Connection;

class GeneDiversityUpdater
{
    const UPDATE_FILTER = " AND updated_gene_diversity = FALSE ";

    /**
     * @param Connection $conn
     * @param boolean $recalculateAllValues
     * @return int
     */
    public static function update(Connection $conn, $recalculateAllValues = false)
    {
        $updateCount = 0;
        $updateCount += self::updateAnimalsWithAMissingParent($conn, $recalculateAllValues);
        $updateCount += self::updateAnimalsHaveBothParentsWhereBreedCodeIsMissingFromAParent($conn, $recalculateAllValues);
        return $updateCount;
    }


    /**
     * @param Connection $conn
     * @param boolean $recalculateAllValues
     * @return int
     */
    private static function updateAnimalsWithAMissingParent(Connection $conn, $recalculateAllValues = false)
    {
        $filter = $recalculateAllValues ? ' ' : self::UPDATE_FILTER;
        $sql = "UPDATE animal SET updated_gene_diversity = TRUE
                WHERE (parent_father_id ISNULL OR animal.parent_mother_id ISNULL) ".$filter;
        return SqlUtil::updateWithCount($conn, $sql);
    }


    /**
     * @param Connection $conn
     * @param boolean $recalculateAllValues
     * @return int
     */
    private static function updateAnimalsHaveBothParentsWhereBreedCodeIsMissingFromAParent(Connection $conn, $recalculateAllValues = false)
    {
        $filter = $recalculateAllValues ? ' ' : self::UPDATE_FILTER;
        $sql = "UPDATE animal SET updated_gene_diversity = TRUE
                WHERE id IN (
                  SELECT c.id FROM animal c
                    INNER JOIN animal f ON f.id = c.parent_father_id
                    INNER JOIN animal m ON m.id = c.parent_mother_id
                  WHERE f.breed_code ISNULL OR m.breed_code ISNULL
                )".$filter;
        return SqlUtil::updateWithCount($conn, $sql);
    }
}