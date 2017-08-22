<?php


namespace AppBundle\Service\Migration;


use AppBundle\Util\SqlUtil;
use AppBundle\Util\StoredProcedure;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Monolog\Logger;

class StoredProcedureInitializer
{
    /** @var EntityManagerInterface */
    private $em;
    /** @var Connection */
    private $conn;
    /** @var Logger */
    private $logger;

    public function __construct(EntityManagerInterface $em, Logger $logger)
    {
        $this->em = $em;
        $this->conn = $em->getConnection();
        $this->logger = $logger;
    }


    public function initialize()
    {
        $updateCount = 0;

        $currentStoredProcedures = $this->getStoredProcedures();
        foreach (StoredProcedure::getConstants() as $routineName)
        {
            if (!key_exists($routineName, $currentStoredProcedures)) {
                StoredProcedure::createOrUpdateProcedure($this->conn, $routineName);
                $updateCount++;
            }
        }

        $count = $updateCount === 0 ? 'No' : $updateCount;
        $this->logger->notice($count . ' SQL stored procedures initialized');
    }


    /**
     * @return array
     */
    private function getStoredProcedures()
    {
        $databaseName = $this->conn->getDatabase();

        $filter = SqlUtil::filterString(StoredProcedure::getConstants(),'routine_name',true);

        $sql = "SELECT routine_name, routine_catalog FROM information_schema.routines
                WHERE ($filter) AND routine_catalog = '$databaseName'";
        return SqlUtil::getSingleValueGroupedSqlResults('routine_name', $this->conn->query($sql)->fetchAll());
    }
}