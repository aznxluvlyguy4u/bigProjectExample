<?php

namespace AppBundle\Entity;
use AppBundle\Util\SqlUtil;

/**
 * Class PedigreeCodeRepository
 * @package AppBundle\Entity
 */
class PedigreeCodeRepository extends BaseRepository {

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getFullNamesByCodes(): array
    {
        $sql = "SELECT
                  code,
                  full_name
                FROM pedigree_code
                WHERE full_name <> '' AND full_name NOTNULL AND code <> '' AND code NOTNULL";
        $result = $this->getConnection()->query($sql)->fetchAll();
        return SqlUtil::groupSqlResultsOfKey1ByKey2('full_name','code', $result,false,false);
    }
}