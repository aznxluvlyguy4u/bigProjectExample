<?php

namespace AppBundle\Output;

use AppBundle\Util\StoredProcedure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AdminDashboardOutput
{
    /**
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function createAdminDashboard(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $sql =
            "SELECT
              'clients' AS key, --Companies
              COUNT(id) AS amount
            FROM company
            WHERE is_active = true
            
            UNION
            
            SELECT
              'invoices' AS key,
              COUNT(id) AS amount
            FROM invoice
            WHERE status = 'UNPAID'
            
            UNION
            
            SELECT
              'requested_inspections' AS key,
              COUNT(id) AS amount
            FROM location_health_inspection WHERE status = 'ANNOUNCED'
            
            UNION
             
             SELECT
              'open_error_messages' AS key,
              COUNT(*) as amount
              FROM (". StoredProcedure::getErrorMessagesSqlQuery($translator, false, true).")v 
              ";

        $results = $em->getConnection()->query($sql)->fetchAll();

        $output = [];

        foreach ($results as $result) {
            $output[$result['key']] = $result['amount'];
        }

        return $output;
    }
}