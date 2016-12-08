<?php

namespace AppBundle\Output;

/**
 * Class HealthInspectionOutput
 */
class HealthInspectionOutput extends Output
{
    public static function filterScrapieInspections($inspections) {
        $results = array();

        foreach($inspections as $inspection) {
            $scrapieEndDate = new \DateTime($inspection["scrapie_check_date"]);
            $now = new \DateTime();
            $interval = $scrapieEndDate->diff($now);

            if ($interval->days >= 42 && $interval->invert == false) {
                $requestDate = $scrapieEndDate->add(new \DateInterval('P42D'));
                $result = array(
                    "ubn" => $inspection["ubn"],
                    "last_name" => $inspection["last_name"],
                    "first_name" => $inspection["first_name"],
                    "inspection" => "SCRAPIE",
                    "request_date" => $requestDate,
                    "next_action" => "SEND FORMS",
                    "directions" => [],
                    "status" => "NEW"
                );

                $results[] = $result;
            };
        }

        return $results;
    }

    public static function filterMaediVisnaInspections($inspections) {
        $results = array();

        foreach($inspections as $inspection) {
            $maediVisnaEndDate = new \DateTime($inspection["maedi_visna_check_date"]);
            $now = new \DateTime();
            $interval = $maediVisnaEndDate->diff($now);
            if ($interval->days >= 42 && $interval->invert == false) {
                $requestDate = $maediVisnaEndDate->add(new \DateInterval('P42D'));
                $result = array(
                    "ubn" => $inspection["ubn"],
                    "last_name" => $inspection["last_name"],
                    "first_name" => $inspection["first_name"],
                    "inspection" => "MAEDI VISNA",
                    "request_date" => $requestDate,
                    "next_action" => "SEND FORMS",
                    "directions" => [],
                    "status" => "NEW"
                );

                $results[] = $result;
            };
        }

        return $results;
    }

    public static function createInspections($inspection, $directions) {
        $latestDirection = $directions[0];
        $status = $inspection["status"];

        if($status == "ONGOING") {
            $directionDate = new \DateTime($latestDirection["direction_date"]);
            $now = new \DateTime();
            $interval = $directionDate->diff($now);

            if($interval->days >= 14) {
                $status = "EXPIRED";
            }
        }

        $result = array(
            "inspection_id" => $inspection["inspection_id"],
            "ubn" => $inspection["ubn"],
            "last_name" => $inspection["last_name"],
            "first_name" => $inspection["first_name"],
            "inspection" => $inspection["inspection_subject"],
            "next_action" => $inspection["next_action"],
            "request_date" => new \DateTime($inspection["request_date"]),
            "direction_date" => $latestDirection["direction_date"],
            "directions" => $directions,
            "total_lead_time" => $inspection["total_lead_time"],
            "action_taken_by" => array(
                "first_name" => $latestDirection["first_name"],
                "last_name" => $latestDirection["last_name"],
            ),
            "status" => $status
        );

        return $result;
    }
}