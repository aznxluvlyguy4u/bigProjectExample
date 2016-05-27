<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;

/**
 * Class HealthOutput
 */
class HealthOutput
{
    /**
     * @param Client $client
     * @param string $ubn
     * @return array
     */
    public static function create(Client $client, $ubn = null)
    {
        if($ubn = null) {
            //TODO Phase 2+ select proper location
            $ubn = $client->getCompanies()->get(0)->getLocations()->get(0)->getUbn();
        }

        $result = array(
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
                  )
        );

        return $result;
    }



}