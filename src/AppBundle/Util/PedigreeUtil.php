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