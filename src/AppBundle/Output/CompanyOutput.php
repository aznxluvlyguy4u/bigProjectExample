<?php

namespace AppBundle\Output;

use AppBundle\Component\Utils;
use AppBundle\Entity\Company;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Client;
use Doctrine\Common\Collections\Collection;
use AppBundle\Component\Count;

class CompanyOutput {

    /**
     * @var $company Company
     *
     * @param Collection $companies
     * @return array
     */
    public static function createCompaniesOverview($companies)
    {
        $res = array();

        foreach($companies as $company) {
            /*  */

            $owner = array(
                'person_id' => $company->getOwner()->getPersonId(),
                'email_address' => $company->getOwner()->getEmailAddress(),
                'first_name' => $company->getOwner()->getFirstName(),
                'last_name' => $company->getOwner()->getLastName()
            );

            $res[] = array(
                'debtor_number' => Utils::fillNull($company->getDebtorNumber()),
                'company_name' => Utils::fillNull($company->getCompanyName()),
                'address' => array(
                    "street_name" => Utils::fillNull($company->getAddress()->getStreetName()),
                    "address_number" => Utils::fillNull($company->getAddress()->getAddressNumber()),
                    "suffix" => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
                    "postal_code" => Utils::fillNull($company->getAddress()->getPostalCode()),
                    "city" => Utils::fillNull($company->getAddress()->getCity()),
                    "state" => Utils::fillNull($company->getAddress()->getState())
                ),
                'owner' => $owner,
                'users' => $company->getCompanyUsers()->filter(
                    function (Client $client) {
                        return $client->getIsActive();
                    }
                ),
                'locations' => $company->getLocations(),
                'pedigrees' => $company->getPedigrees(),
                'unpaid_invoices' => $company->getInvoices()->filter(
                    function (Invoice $invoice) {
                        return $invoice->getStatus() == 'OPEN';
                    }
                ),
                'subscription_date' => Utils::fillNull($company->getDebtorNumber()),
                'animal_health_subscription' => $company->getAnimalHealthSubscription(),
                'status' => $company->isActive()
            );
        }

        return $res;
    }


    /**
     * @param Company $company
     * @return array
     */
    public static function createCompanyDetails($company)
    {
        $res = array();
        $res["company_name"] = Utils::fillNull($company->getCompanyName());
        $res["telephone_number"] = Utils::fillNull($company->getTelephoneNumber());
        $res["owner"] = array(
            'email_address' => Utils::fillNull($company->getOwner()->getEmailAddress()),
            'prefix' => Utils::fillNull($company->getOwner()->getPrefix()),
            'first_name' => Utils::fillNull($company->getOwner()->getFirstName()),
            'last_name' => Utils::fillNull($company->getOwner()->getLastName())
        );
        $res["status"] = $company->isActive();
        $res["subscription_date"] = $company->getSubscriptionDate();
        $res["address"] = array(
            "street_name" => Utils::fillNull($company->getAddress()->getStreetName()),
            "address_number" => Utils::fillNull($company->getAddress()->getAddressNumber()),
            "suffix" => Utils::fillNull($company->getAddress()->getAddressNumberSuffix()),
            "postal_code" => Utils::fillNull($company->getAddress()->getPostalCode()),
            "city" => Utils::fillNull($company->getAddress()->getCity()),
            "state" => Utils::fillNull($company->getAddress()->getState())
        );

        $liveStockCount = Count::getCompanyLiveStockCount($company);
        $res["livestock"] = array(
            "ram" => array(
                "total" => $liveStockCount["RAM_TOTAL"],
                "less_6_months" => $liveStockCount["RAM_UNDER_SIX"],
                "between_6_12_months" => $liveStockCount["RAM_BETWEEN_SIX_AND_TWELVE"],
                "greater_12_months" => $liveStockCount["RAM_OVER_TWELVE"]
            ),
            "ewe" => array(
                "total" => $liveStockCount["EWE_TOTAL"],
                "less_6_months" => $liveStockCount["EWE_UNDER_SIX"],
                "between_6_12_months" => $liveStockCount["EWE_BETWEEN_SIX_AND_TWELVE"],
                "greater_12_months" => $liveStockCount["EWE_OVER_TWELVE"]
            ),
            "neuter" => array(
                "total" => $liveStockCount["NEUTER_TOTAL"],
                "less_6_months" => $liveStockCount["NEUTER_UNDER_SIX"],
                "between_6_12_months" => $liveStockCount["NEUTER_BETWEEN_SIX_AND_TWELVE"],
                "greater_12_months" => $liveStockCount["NEUTER_OVER_TWELVE"]
            ),
        );

        return $res;
    }
}

//    {
//        "result": {
//        "client_id": 1,
//    "company_name": "Marta Fokkerij",
//    "telephone_number": "+31-123451234",
//    "primary_contactperson": {
//            "prefix": "MISS",
//      "first_name": "Brink",
//      "last_name": "Marta",
//      "email_address": "marta.brink@martafokkerij.nl"
//    },
//    "status": "ACTIVE",
//    "subscription_date": "2016-03-31T22:00:00.000Z",
//    "address": {
//            "street_name": "Postmanlaan",
//      "address_number": "90",
//      "suffix": "a",
//      "postal_code": "1234AB",
//      "city": "Den Haag",
//      "state": "ZH",
//      "country": "NL"
//    },
//    "livestock": {
//            "ram": {
//                "total": 74,
//        "less_6_months": 6,
//        "between_6_12_months": 18,
//        "greater_12_months": 50
//      },
//      "ewe": {
//                "total": 61,
//        "less_6_months": 18,
//        "between_6_12_months": 6,
//        "greater_12_months": 37
//      }
//    },
//    "breeder_numbers": [
//      {
//          "code": "NTS",
//        "number": "000756"
//      },
//      {
//          "code": "SBT",
//        "number": "26045"
//      },
//      {
//          "code": "CF",
//        "number": "00EBG"
//      }
//    ],
//    "invoices": [
//      {
//          "invoice_number": "000001",
//        "invoice_date": "2016-03-31T22:00:00.000Z",
//        "status": "UNPAID",
//        "pdf_url": "#"
//      },
//      {
//          "invoice_number": "000002",
//        "invoice_date": "2016-03-25T22:00:00.000Z",
//        "status": "PAID",
//        "pdf_url": "#"
//      }
//    ],
//    "animal_health": [
//      {
//          "ubn": "1111111",
//        "name": "Everts",
//        "inspection": "SCRAPIE",
//        "request_date": "2016-03-01T22:00:00.000Z",
//        "directions": [
//          {
//              "type": "Aanstuurdatum",
//            "date": "2016-03-01T22:00:00.000Z"
//          },
//          {
//              "type": "Monstername datum",
//            "date": "2016-03-03T22:00:00.000Z"
//          }
//        ],
//        "total_lead_time": "18 dagen van monstername tot autorisatie",
//        "authorized_by": "Marjo van Bergen"
//      }
//    ]
//  }
//}