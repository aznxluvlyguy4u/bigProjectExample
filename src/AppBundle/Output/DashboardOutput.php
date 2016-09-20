<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Component\Count;
use AppBundle\Entity\Content;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\RequestTypeNonIR;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;


/**
 * Class DashboardOutput
 */
class DashboardOutput extends Output
{
    /**
     * @param Client $client
     * @param ArrayCollection $declarationLogDate
     * @param Location $location
     * @return array
     */
    public static function create(ObjectManager $em, Client $client, ArrayCollection $declarationLogDate, $location)
    {
        $liveStockCount = Count::getLiveStockCountLocation($location);
        $errorCounts = Count::getErrorCountDeclarationsPerLocation($location);
        $unassignedTagsCount = Count::getUnassignedTagsCount($client);

        self:: setUbnAndLocationHealthValues($em, $location);

        $repository = $em->getRepository(Content::class);
        $cms = $repository->getCMS();

        $result = array(
                  "introduction" =>  $cms->getDashBoardIntroductionText(),
                  "ubn" => self::$ubn,
                  "health_status" =>
                  array(
                    "location_health_status" => self::$locationHealthStatus,
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
                      "date_last_declaration" => $declarationLogDate->get(RequestTypeNonIR::MATE),
                      "error_count" => $errorCounts->get(RequestTypeNonIR::MATE)
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