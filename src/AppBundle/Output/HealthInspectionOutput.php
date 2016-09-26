<?php

namespace AppBundle\Output;

/**
 * Class HealthInspectionOutput
 */
class HealthInspectionOutput extends Output
{
    public static function createNewScrapieInspections($inspections) {
        $results = array();

        foreach($inspections as $inspection) {
            $scrapieEndDate = new \DateTime($inspection["scrapie_check_date"]);
            $now = new \DateTime();
            $interval = $scrapieEndDate->diff($now);

            if ($interval->days >= 42) {
                $requestDate = $scrapieEndDate->add(new \DateInterval('P42D'));
                $result = array(
                    "ubn" => $inspection["ubn"],
                    "last_name" => $inspection["last_name"],
                    "first_name" => $inspection["first_name"],
                    "inspection" => "SCRAPIE",
                    "request_date" => $requestDate,
                    "direction_date" => "",
                    "next_action" => "SEND FORMS",
                    "action_taken_by" => "",
                    "sampling_date" => "",
                    "data" => "",
                    "total_lead_time" => "",
                    "authorized_by" => "",
                    "status" => "NEW"
                );

                $results[] = $result;
            };
        }

        return $results;
    }

    public static function createInspections($inspections) {
        $results = array();

        foreach($inspections as $inspection) {
            $result = array(
                "inspection_id" => $inspection["inspection_id"],
                "ubn" => $inspection["ubn"],
                "last_name" => $inspection["last_name"],
                "first_name" => $inspection["first_name"],
                "inspection" => $inspection["inspection_subject"],
                "next_action" => $inspection["next_action"],
                "action_taken_by" => $inspection["action_taker_first_name"] . " " . $inspection["action_taker_last_name"],
                "request_date" => new \DateTime($inspection["request_date"]),
                "direction_date" => "",
                "sampling_date" => "",
                "data" => "",
                "total_lead_time" => $inspection["total_lead_time"],
                "authorized_by" => $inspection["authorizer_first_name"] . " " . $inspection["authorizer_last_name"],
                "status" => $inspection["status"]
            );

            $results[] = $result;
        };
        return $results;
    }
}