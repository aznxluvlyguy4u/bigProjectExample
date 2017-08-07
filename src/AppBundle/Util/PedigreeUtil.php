<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

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
     * @param Connection $conn
     * @param int $locationId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function findAnimalAndAscendantsOfLocationIdByJoinedSql(Connection $conn, $locationId)
    {
        $groupedResults = [];
        if(!ctype_digit($locationId) && !is_int($locationId)) { return $groupedResults; }
        
        $sql = "SELECT c.id as c_id, m.id as m_id, f.id as f_id, mm.id as mm_id, fm.id as fm_id, mf.id as mf_id, ff.id as ff_id,
                      mmm.id as mmm_id, fmm.id as fmm_id, mfm.id as mfm_id, ffm.id as ffm_id,
                      mmf.id as mmf_id, fmf.id as fmf_id, mff.id as mff_id, fff.id as fff_id
                FROM animal c
                  --parents
                LEFT JOIN animal m ON m.id = c.parent_mother_id
                LEFT JOIN animal f ON f.id = c.parent_father_id
                  --grand parents
                  LEFT JOIN animal mm ON mm.id = m.parent_mother_id
                  LEFT JOIN animal fm ON fm.id = f.parent_mother_id
                  LEFT JOIN animal mf ON mf.id = m.parent_father_id
                  LEFT JOIN animal ff ON ff.id = f.parent_father_id
                  --great grand parents
                    LEFT JOIN animal mmm ON mmm.id = mm.parent_mother_id
                    LEFT JOIN animal fmm ON fmm.id = fm.parent_mother_id
                    LEFT JOIN animal mfm ON mfm.id = mf.parent_mother_id
                    LEFT JOIN animal ffm ON ffm.id = ff.parent_mother_id
                    LEFT JOIN animal mmf ON mmf.id = mm.parent_father_id
                    LEFT JOIN animal fmf ON fmf.id = fm.parent_father_id
                    LEFT JOIN animal mff ON mff.id = mf.parent_father_id
                    LEFT JOIN animal fff ON fff.id = ff.parent_father_id
                WHERE c.id IN(
                  --Get historic animals
                  SELECT b.id FROM animal b
                    INNER JOIN animal_residence r ON r.animal_id = b.id
                  WHERE r.location_id = ".$locationId."
                  GROUP BY b.id
                  UNION
                  --Get current animals
                  SELECT c.id FROM animal c
                  WHERE c.location_id = ".$locationId."
                  ORDER BY id
                )";
        $results = $conn->query($sql)->fetchAll();

        foreach ($results as $result) {
            foreach ($result as $value) {
                if($value != null) {
                    $groupedResults[$value] = $value;
                }
            }
        }

        return $groupedResults;
    }


    /**
     * @return array
     */
    public function getParentIds()
    {
        return $this->parentIds;
    }

}