<?php

namespace AppBundle\Output;

use AppBundle\Entity\Invoice;
use AppBundle\Entity\Location;
use AppBundle\Entity\LocationHealthInspection;
use Doctrine\ORM\EntityManager;
use AppBundle\Constant\Constant;
use AppBundle\Entity\Company;

class AdminDashboardOutput
{
    public static function createAdminDashboard(EntityManager $em)
    {
        $repository = $em->getRepository(Constant::COMPANY_REPOSITORY);
        $companies = $repository->findAll();

        $usersAmount = 0;
        foreach ($companies as $company) {
            /**
             * @var Company $company
             */
            if($company->getOwner()) {
                $usersAmount += 1;
            }

            $usersAmount += $company->getCompanyUsers()->count();
        }

        $invoicesAmount = 0;
        foreach ($companies as $company) {
            /**
             * @var Company $company
             */
            $invoices = $company->getInvoices();

            foreach($invoices as $invoice) {
                /**
                 * @var Invoice $invoice
                 */
                if($invoice->getStatus() == "UNPAID") {
                    $invoicesAmount += 1;
                }
            }
        }

        $inspectionsAmount = 0;
        foreach ($companies as $company) {
            $locations = $company->getLocations();

            foreach ($locations as $location) {
                /**
                 * @var Location $location
                 */
                $inspections = $location->getInspections();

                foreach ($inspections as $inspection) {
                    /**
                     * @var LocationHealthInspection $inspection
                     */
                    if($inspection->getStatus() == "ANNOUNCED") {
                        $inspectionsAmount += 1;
                    }
                }
            }
        }


        $results = array();
        $results['users'] = array("amount" => $usersAmount);
        $results['invoices'] = array("unpaid" => $invoicesAmount);
        $results['health'] = array("announced" => $inspectionsAmount);

        return $results;
    }

}