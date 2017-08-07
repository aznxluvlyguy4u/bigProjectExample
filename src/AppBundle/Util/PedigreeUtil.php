<?php


namespace AppBundle\Util;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;

class PedigreeUtil
{
    const MAX_GENERATION = 3;
    const CHILD_PARAM = 'c';
    const FATHER_PARAM = 'f';
    const MOTHER_PARAM = 'm';

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


    /**
     * @param Connection $conn
     * @param array $animalIds
     * @param int $generationLimit
     * @return string
     */
    public static function findParentsBySingleSqlQuery(Connection $conn, array $animalIds, $generationLimit = 1)
    {
        $sql = 'SELECT '.implode(', ', self::ulnSelectString($generationLimit))
             .' FROM animal '.self::CHILD_PARAM
             .' '.implode(' ', self::joinParents($generationLimit))
             .' WHERE '.SqlUtil::getFilterStringByIdsArray($animalIds, self::CHILD_PARAM.'.id');
        ;
        if(count($animalIds) == 1) {
            return $conn->query($sql)->fetch();
        }
        return $conn->query($sql)->fetchAll();
    }

    /**
     * @param $animalParameter
     * @param array $sqlSelectParts
     * @param int $generationLimit
     * @param int $generationLevel
     * @return array
     */
    private static function ulnSelectString($generationLimit, $animalParameter = self::CHILD_PARAM , $sqlSelectParts = [], $generationLevel = 0)
    {
        if($animalParameter == self::CHILD_PARAM) {
            $sqlSelectParts[$animalParameter] = self::concatUlnString(self::CHILD_PARAM);
        }

        if($generationLevel == $generationLimit) {
            return $sqlSelectParts;
        }

        $fatherParameter = self::fatherParameter($animalParameter);
        $motherParameter = self::motherParameter($animalParameter);

        $sqlSelectParts[$fatherParameter] = self::concatUlnString($fatherParameter);
        $sqlSelectParts[$motherParameter] = self::concatUlnString($motherParameter);

        $generationLevel++;

        if($generationLevel < $generationLimit) {

            $sqlSelectParts = self::ulnSelectString($generationLimit, $fatherParameter, $sqlSelectParts, $generationLevel);
            $sqlSelectParts = self::ulnSelectString($generationLimit, $motherParameter, $sqlSelectParts, $generationLevel);
        }

        return $sqlSelectParts;
    }

    /**
     * @param string $animalParameter
     * @return string
     */
    private static function concatUlnString($animalParameter)
    {
        return
            $animalParameter.'.id as '.$animalParameter.'_id'
            .', CONCAT('.$animalParameter.'.uln_country_code,'.$animalParameter.'.uln_number) as '.$animalParameter.'_uln'
            .', CONCAT('.$animalParameter.'.pedigree_country_code,'.$animalParameter.'.pedigree_number) as '.$animalParameter.'_stn'
            ;
    }

    /**
     * @param string $animalParameter
     * @param array $sqlJoinParts
     * @param int $generationLimit
     * @param int $generationLevel
     * @return string
     */
    private static function joinParents($generationLimit, $animalParameter = self::CHILD_PARAM, $sqlJoinParts = [], $generationLevel = 0)
    {
        $fatherParameter = self::fatherParameter($animalParameter);
        $motherParameter = self::motherParameter($animalParameter);

        $sqlJoinParts[$fatherParameter] = 'LEFT JOIN animal '.$fatherParameter.' ON '.$fatherParameter.'.id = '.$animalParameter.'.parent_father_id';
        $sqlJoinParts[$motherParameter] = 'LEFT JOIN animal '.$motherParameter.' ON '.$motherParameter.'.id = '.$animalParameter.'.parent_mother_id';

        $generationLevel++;

        if($generationLevel < $generationLimit) {

            $sqlJoinParts = self::joinParents($generationLimit, $fatherParameter, $sqlJoinParts, $generationLevel);
            $sqlJoinParts = self::joinParents($generationLimit, $motherParameter, $sqlJoinParts, $generationLevel);
        }
        return $sqlJoinParts;
    }


    /**
     * @param string $animalParameter
     * @return string
     */
    private static function fatherParameter($animalParameter)
    {
        return $animalParameter == self::CHILD_PARAM ? self::FATHER_PARAM : $animalParameter.self::FATHER_PARAM;
    }

    /**
     * @param string $animalParameter
     * @return string
     */
    private static function motherParameter($animalParameter)
    {
        return $animalParameter == self::CHILD_PARAM ? self::MOTHER_PARAM : $animalParameter.self::MOTHER_PARAM;
    }
}