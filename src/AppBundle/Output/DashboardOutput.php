<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Enumerator\LiveStockType;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Class DashboardOutput
 */
class DashboardOutput
{
    /**
     * @param Client $client
     * @param ArrayCollection $liveStockCount
     * @return array
     */
    public static function create(Client $client, ArrayCollection $liveStockCount)
    {
        $ubn = $client->getCompanies()->get(0)->getLocations()->get(0)->getUbn(); //TODO Phase 2+ select proper location

        $result = array(
                  "introduction" => "Welkom! Geniet van ons nieuw systeem.",
                  "ubn" => $ubn,
                  "health_status" =>
                  array(
                    "company_health_status" => "",
                    //maedi_visna is zwoegerziekte
                    "maedi_visna_status" => "",
                    "maedi_visna_end_date" => "",
                    "scrapie_status" => "",
                    "scrapie_end_date" => "",
                    "check_date" => ""
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
                      "date_last_declaration" => "",
                      "error_count" => ""
                  ),
                  "depart" => //including export
                  array(
                      "date_last_declaration" => "",
                      "error_count" => ""
                  ),
                  "birth" =>
                  array(
                      "date_last_declaration" => "",
                      "error_count" => ""
                  ),
                  "mate" =>
                  array(
                      "date_last_declaration" => "",
                      "error_count" => ""
                  ),
                  "loss" =>
                  array(
                      "date_last_declaration" => "",
                      "error_count" => ""
                  ),
                  "tag_transfer" =>
                  array(
                      "date_last_declaration" => "",
                      "unassigned_tags" => ""
                  )
        );

        return $result;
    }



}