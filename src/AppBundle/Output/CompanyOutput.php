<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Entity\Company;
use AppBundle\Entity\Location;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Client;
use AppBundle\Entity\Pedigree;
use Doctrine\Common\Collections\Collection;
use AppBundle\Component\Count;

class CompanyOutput
{
    /**
     * @param Collection $companies
     *
     * @return array
     */
    public static function createCompaniesOverview($companies)
    {
        $res = array();

        foreach ($companies as $company) {

            /*
             * @var $company Company
             */
            $locations = $company->getLocations();
            $ubns = array();
            foreach ($locations as $location) {

                /*
                 * @var $location Location
                 */
                $ubns[] = $location->getUbn();
            }

            $pedigrees = $company->getPedigrees();
            $pedigreeNumbers = array();
            foreach ($pedigrees as $pedigree) {

                /*
                 * @var $pedigree Pedigree
                 */
                $pedigreeNumbers[] = $pedigree->getPedigreeCode();
            }

            $users = $company->getCompanyUsers();
            $resUsers = array();
            foreach ($users as $user) {
                /**
                 * @var Client $user
                 */

                if($user->getIsActive()) {
                    $newUser = array();
                    $newUser['person_id'] = $user->getPersonId();
                    $newUser['prefix'] = $user->getPrefix();
                    $newUser['first_name'] = $user->getFirstName();
                    $newUser['last_name'] = $user->getLastName();
                    $newUser['email_address'] = $user->getEmailAddress();
                    $resUsers[] = $newUser;
                }
            }

            $res[] = array(
                'company_id' => Utils::fillNull($company->getCompanyId()),
                'debtor_number' => Utils::fillNull($company->getDebtorNumber()),
                'company_name' => Utils::fillNull($company->getCompanyName()),
                'address' => array(
                    'street_name' => Utils::fillNull($company->getAddress()->getStreetName()),
                    'address_number' => Utils::fillNull($company->getAddress()->getAddressNumber()),
                    'suffix' => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
                    'postal_code' => Utils::fillNull($company->getAddress()->getPostalCode()),
                    'city' => Utils::fillNull($company->getAddress()->getCity()),
                    'state' => Utils::fillNull($company->getAddress()->getState()),
                ),
                'owner' => array(
                    'person_id' => $company->getOwner()->getPersonId(),
                    'email_address' => $company->getOwner()->getEmailAddress(),
                    'first_name' => $company->getOwner()->getFirstName(),
                    'last_name' => $company->getOwner()->getLastName(),
                ),
                'users' => $resUsers,
                'locations' => $ubns,
                'pedigrees' => $pedigreeNumbers,
                'unpaid_invoices' => $company->getInvoices()->filter(
                    function (Invoice $invoice) {
                        return $invoice->getStatus() == 'OPEN';
                    }
                ),
                'subscription_date' => Utils::fillNull($company->getDebtorNumber()),
                'animal_health_subscription' => $company->getAnimalHealthSubscription(),
                'status' => $company->isActive(),
            );
        }

        return $res;
    }

    /**
     * @param Company $company
     *
     * @return array
     */
    public static function createCompany($company)
    {
        $res = array();

        $res['company_id'] = Utils::fillNull($company->getCompanyId());
        $res['company_name'] = Utils::fillNull($company->getCompanyName());
        $res['telephone_number'] = Utils::fillNull($company->getTelephoneNumber());
        $res['company_relation_number'] = Utils::fillNull($company->getCompanyRelationNumber());
        $res['debtor_number'] = Utils::fillNull($company->getDebtorNumber());
        $res['vat_number'] = Utils::fillNull($company->getVatNumber());
        $res['chamber_of_commerce_number'] = Utils::fillNull($company->getChamberOfCommerceNumber());
        $res['animal_health_subscription'] = Utils::fillNull($company->getAnimalHealthSubscription());

        $res['owner'] = array(
            'person_id' => $company->getOwner()->getPersonId(),
            'prefix' => $company->getOwner()->getPrefix(),
            'first_name' => $company->getOwner()->getFirstName(),
            'last_name' => $company->getOwner()->getLastName(),
            'email_address' => $company->getOwner()->getEmailAddress(),
        );

        $res['address'] = array(
            'street_name' => Utils::fillNull($company->getAddress()->getStreetName()),
            'address_number' => Utils::fillNull($company->getAddress()->getAddressNumber()),
            'suffix' => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
            'postal_code' => Utils::fillNull($company->getAddress()->getPostalCode()),
            'city' => Utils::fillNull($company->getAddress()->getCity()),
            'state' => Utils::fillNull($company->getAddress()->getState()),
            'country' => Utils::fillNull($company->getAddress()->getCountry()),
        );

        $res['billing_address'] = array(
            'street_name' => Utils::fillNull($company->getBillingAddress()->getStreetName()),
            'address_number' => Utils::fillNull($company->getBillingAddress()->getAddressNumber()),
            'suffix' => Utils::fillNull($company->getBillingAddress()->getAddressNumberSuffix()),
            'postal_code' => Utils::fillNull($company->getBillingAddress()->getPostalCode()),
            'city' => Utils::fillNull($company->getBillingAddress()->getCity()),
            'state' => Utils::fillNull($company->getBillingAddress()->getState()),
            'country' => Utils::fillNull($company->getBillingAddress()->getCountry()),
        );

        $locations = $company->getLocations();
        $res['locations'] = [];
        foreach ($locations as $location) {
            if($location->getIsActive()) {
                $newLocation = array();
                $newLocation['location_id'] = $location->getLocationId();
                $newLocation['ubn'] = $location->getUbn();
                $newLocation['is_active'] = $location->getIsActive();
                $newLocation['address'] = array(
                    'street_name' => Utils::fillNull($location->getAddress()->getStreetName()),
                    'address_number' => Utils::fillNull($location->getAddress()->getAddressNumber()),
                    'suffix' => Utils::fillNull($location->getAddress()->getAddressNumberSuffix()),
                    'postal_code' => Utils::fillNull($location->getAddress()->getPostalCode()),
                    'city' => Utils::fillNull($location->getAddress()->getCity()),
                    'state' => Utils::fillNull($location->getAddress()->getState()),
                    'country' => Utils::fillNull($location->getAddress()->getCountry()),
                );
                $res['locations'][] = $newLocation;
            }
        }

        $users = $company->getCompanyUsers();
        $res['users'] = [];
        foreach ($users as $user) {
            /**
             * @var Client $user
             */

            if($user->getIsActive()) {
                $newUser = array();
                $newUser['person_id'] = $user->getPersonId();
                $newUser['prefix'] = $user->getPrefix();
                $newUser['first_name'] = $user->getFirstName();
                $newUser['last_name'] = $user->getLastName();
                $newUser['email_address'] = $user->getEmailAddress();
                $res['users'][] = $newUser;
            }
        }
        return $res;
    }

    /**
     * @param Company $company
     *
     * @return array
     */
    public static function createCompanyDetails($company)
    {
        $res = array();
        $res['company_id'] = Utils::fillNull($company->getCompanyId());
        $res['company_name'] = Utils::fillNull($company->getCompanyName());
        $res['telephone_number'] = Utils::fillNull($company->getTelephoneNumber());
        $res['owner'] = array(
            'person_id' => $company->getOwner()->getPersonId(),
            'email_address' => Utils::fillNull($company->getOwner()->getEmailAddress()),
            'prefix' => Utils::fillNull($company->getOwner()->getPrefix()),
            'first_name' => Utils::fillNull($company->getOwner()->getFirstName()),
            'last_name' => Utils::fillNull($company->getOwner()->getLastName()),
        );
        $res['status'] = $company->isActive();
        $res['subscription_date'] = Utils::fillNull($company->getSubscriptionDate());
        $res['address'] = array(
            'street_name' => Utils::fillNull($company->getAddress()->getStreetName()),
            'address_number' => Utils::fillNull($company->getAddress()->getAddressNumber()),
            'suffix' => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
            'postal_code' => Utils::fillNull($company->getAddress()->getPostalCode()),
            'city' => Utils::fillNull($company->getAddress()->getCity()),
            'state' => Utils::fillNull($company->getAddress()->getState()),
        );

        $liveStockCount = Count::getCompanyLiveStockCount($company);
        $res['livestock'] = array(
            'ram' => array(
                'total' => $liveStockCount['RAM_TOTAL'],
                'less_6_months' => $liveStockCount['RAM_UNDER_SIX'],
                'between_6_12_months' => $liveStockCount['RAM_BETWEEN_SIX_AND_TWELVE'],
                'greater_12_months' => $liveStockCount['RAM_OVER_TWELVE'],
            ),
            'ewe' => array(
                'total' => $liveStockCount['EWE_TOTAL'],
                'less_6_months' => $liveStockCount['EWE_UNDER_SIX'],
                'between_6_12_months' => $liveStockCount['EWE_BETWEEN_SIX_AND_TWELVE'],
                'greater_12_months' => $liveStockCount['EWE_OVER_TWELVE'],
            ),
            'neuter' => array(
                'total' => $liveStockCount['NEUTER_TOTAL'],
                'less_6_months' => $liveStockCount['NEUTER_UNDER_SIX'],
                'between_6_12_months' => $liveStockCount['NEUTER_BETWEEN_SIX_AND_TWELVE'],
                'greater_12_months' => $liveStockCount['NEUTER_OVER_TWELVE'],
            ),
        );

        $res['breeder_numbers'] = array();
        $res['invoices'] = $company->getInvoices();

        /*
         * @var $company Company
         */
        $locations = $company->getLocations();
        $healthInspections = array();
        foreach ($locations as $location) {

            /*
             * @var $location Location
             */
            $healthInspections[] = $location->getInspections();
        }

        $users = $company->getCompanyUsers();
        $res['users'] = [];
        foreach ($users as $user) {
            /**
             * @var Client $user
             */

            if($user->getIsActive()) {
                $newUser = array();
                $newUser['person_id'] = $user->getPersonId();
                $newUser['prefix'] = $user->getPrefix();
                $newUser['first_name'] = $user->getFirstName();
                $newUser['last_name'] = $user->getLastName();
                $newUser['email_address'] = $user->getEmailAddress();
                $res['users'][] = $newUser;
            }
        }

        $res['health_inspections'] = $healthInspections;

        return $res;
    }
}
