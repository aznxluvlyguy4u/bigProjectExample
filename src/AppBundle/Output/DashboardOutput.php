<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Component\Count;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestType;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Class DashboardOutput
 */
class DashboardOutput extends Output
{
    /**
     * @param Client $client
     * @param ArrayCollection $liveStockCount
     * @return array
     */
    public static function create(Client $client, ArrayCollection $declarationLogDate)
    {
        $liveStockCount = Count::getLiveStockCount($client);
        $errorCounts = Count::getErrorCountDeclarations($client);
        $unassignedTagsCount = Count::getUnassignedTagsCount($client);

        //TODO Phase 2+ select proper location
        $location = $client->getCompanies()->get(0)->getLocations()->get(0);
        self:: setUbnAndLocationHealthValues($location);

        $result = array(
                  "introduction" => "Welkom! Geniet van ons nieuw systeem.",
                  "ubn" => self::$ubn,
                  "health_status" =>
                  array(
                    //TODO refactor 'company_health_status'  to 'location_health_status'
                    "company_health_status" => self::$locationHealthStatus,
                    //maedi_visna is zwoegerziekte
                      "maedi_visna_status" => self::$maediVisnaStatus,
                      "maedi_visna_end_date" => self::$maediVisnaEndDate,
                      "scrapie_status" => self::$scrapieStatus,
                      "scrapie_end_date" => self::$scrapieEndDate,
                      "check_date" => self::$checkDate
                  ),
                  "livestock" =>
                  array(
                    "pedigree_animals" =>
                    array(
                        "total" => $liveStockCount[LiveStockType::PEDIGREE_TOTAL],
                        "adults" => $liveStockCount[LiveStockType::PEDIGREE_ADULT],
                        "lambs" => $liveStockCount[LiveStockType::PEDIGREE_LAMB]
                    ),
                    "non_pedigree_animals" =>
                    array(
                        "total" => $liveStockCount[LiveStockType::NON_PEDIGREE_TOTAL],
                        "adults" => $liveStockCount[LiveStockType::NON_PEDIGREE_ADULT],
                        "lambs" => $liveStockCount[LiveStockType::NON_PEDIGREE_LAMB]
                    ),
                    "all_animals" =>
                    array(
                        "total" => $liveStockCount[LiveStockType::TOTAL],
                        "adults" => $liveStockCount[LiveStockType::ADULT],
                        "lambs" => $liveStockCount[LiveStockType::LAMB]
                    )
                  ),
                  "arrival" => //including import
                  array(
                      "date_last_declaration" => $declarationLogDate->get(RequestType::DECLARE_ARRIVAL_ENTITY),
                      "error_count" => $errorCounts->get(RequestType::DECLARE_ARRIVAL)
                  ),
                  "depart" => //including export
                  array(
                      "date_last_declaration" => $declarationLogDate->get(RequestType::DECLARE_DEPART_ENTITY),
                      "error_count" => $errorCounts->get(RequestType::DECLARE_DEPART)
                  ),
                  "birth" =>
                  array(
                      "date_last_declaration" => $declarationLogDate->get(RequestType::DECLARE_BIRTH_ENTITY),
                      "error_count" => $errorCounts->get(RequestType::DECLARE_BIRTH)
                  ),
                  "mate" =>
                  array(
                      "date_last_declaration" => "",
                      "error_count" => ""
                  ),
                  "loss" =>
                  array(
                      "date_last_declaration" => $declarationLogDate->get(RequestType::DECLARE_LOSS_ENTITY),
                      "error_count" => $errorCounts->get(RequestType::DECLARE_LOSS)
                  ),
                  "tag_transfer" =>
                  array(
                      "date_last_declaration" => $declarationLogDate->get(RequestType::DECLARE_TAGS_TRANSFER_ENTITY),
                      "unassigned_tags" => $unassignedTagsCount
                  )
        );

        return $result;
    }



}