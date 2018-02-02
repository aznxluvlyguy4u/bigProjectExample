<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Component\Count;
use AppBundle\Entity\Content;
use AppBundle\Entity\ContentRepository;
use AppBundle\Entity\Location;
use AppBundle\Enumerator\LiveStockType;
use AppBundle\Enumerator\RequestType;
use AppBundle\Enumerator\RequestTypeNonIR;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;


/**
 * Class DashboardOutput
 */
class DashboardOutput extends Output
{
    /**
     * @param Client $client
     * @param ArrayCollection $declarationLogDate
     * @param Location $location
     * @param EntityManagerInterface $em
     * @return array
     * @throws DBALException
     */
    public static function create(EntityManagerInterface $em, Client $client, ArrayCollection $declarationLogDate, $location)
    {
        $liveStockCount = Count::getLiveStockCountLocation($em, $location);
        $errorCounts = Count::getErrorCountDeclarationsPerLocation($em, $location);
        $tagCounts = Count::getTagsCount($em, $client->getId(), $location->getId());

        self:: setUbnAndLocationHealthValues($em, $location);

        /** @var ContentRepository $repository */
        $repository = $em->getRepository(Content::class);
        $dashBoardIntroductionText = $repository->getDashBoardIntroductionText();

        $healthStatus = [];
        if ($location && $location->getAnimalHealthSubscription()) {
            $healthStatus = [
                "location_health_status" => self::$locationHealthStatus,
                //maedi_visna is zwoegerziekte
                "maedi_visna_status" => self::$maediVisnaStatus,
                "maedi_visna_check_date" => self::$maediVisnaCheckDate,
                "scrapie_status" => self::$scrapieStatus,
                "scrapie_check_date" => self::$scrapieCheckDate,
            ];
        }

        $result = array(
                  "introduction" =>  $dashBoardIntroductionText,
                  "ubn" => self::$ubn,
                  "health_status" =>
                  $healthStatus,
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
                      "unassigned_tags" => $tagCounts[Count::FREE],
                      "used_tags" => $tagCounts[Count::USED],
                  )
        );

        return $result;
    }



}