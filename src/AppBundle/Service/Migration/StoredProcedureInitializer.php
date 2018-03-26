<?php


namespace AppBundle\Service\Migration;


use AppBundle\Util\SqlUtil;
use AppBundle\Util\StoredProcedure;

class StoredProcedureInitializer extends DatabaseContentInitializerBase implements DatabaseContentInitializerInterface
{

    public function initialize()
    {
        $this->upsert(false);
    }


    public function update()
    {
        $this->upsert(true);
    }


    /**
     * @param boolean $overwriteOldVersions
     */
    public function upsert($overwriteOldVersions)
    {
        $updateCount = 0;

        $currentStoredProcedures = $this->getStoredProcedures();
        foreach (StoredProcedure::getConstants() as $routineName)
        {
            if (!key_exists($routineName, $currentStoredProcedures) || $overwriteOldVersions) {
                StoredProcedure::createOrUpdateProcedure($this->getConnection(), $this->getTranslator(), $routineName);
                $updateCount++;
            }
        }

        $count = $updateCount === 0 ? 'No' : $updateCount;
        $this->getLogger()->notice($count . ' SQL stored procedures initialized');
    }


    /**
     * @return array
     */
    private function getStoredProcedures()
    {
        $databaseName = $this->getConnection()->getDatabase();

        $filter = SqlUtil::filterString(StoredProcedure::getConstants(),'routine_name',true);

        $sql = "SELECT routine_name, routine_catalog FROM information_schema.routines
                WHERE ($filter) AND routine_catalog = '$databaseName'";
        return SqlUtil::getSingleValueGroupedSqlResults('routine_name', $this->getConnection()->query($sql)->fetchAll());
    }
}