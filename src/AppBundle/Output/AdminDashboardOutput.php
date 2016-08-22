<?php

namespace AppBundle\Output;

use AppBundle\Entity\Invoice;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthInspection;
use Doctrine\ORM\EntityManager;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;
use Doctrine\ORM\Query;

class AdminDashboardOutput
{
    public static function createAdminDashboard(EntityManager $em)
    {
        $results = array();

        // Companies
        $sql = 'SELECT COUNT(\'id\') AS amount FROM company WHERE is_active = true';
        $result = $em->getConnection()->query($sql)->fetch();
        $results['clients'] = $result['amount'];

        // Invoices
        $sql = 'SELECT COUNT(\'id\') AS amount FROM invoice WHERE status = \'UNPAID\'';
        $result = $em->getConnection()->query($sql)->fetch();
        $results['invoices'] = $result['amount'];

        // Inspections
        $sql = 'SELECT COUNT(\'id\') AS amount FROM location_health_inspection WHERE status = \'ANNOUNCED\'';
        $result = $em->getConnection()->query($sql)->fetch();
        $results['requested_inspections'] = $result['amount'];

        return $results;
    }
}