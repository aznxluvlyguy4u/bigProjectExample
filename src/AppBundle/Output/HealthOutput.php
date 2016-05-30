<?php

namespace AppBundle\Output;
use AppBundle\Entity\Client;
use AppBundle\Entity\Location;

/**
 * Class HealthOutput
 */
class HealthOutput extends Output
{
    /**
     * @param Client $client
     * @param Location $location
     * @return array
     */
    public static function create(Client $client, Location $location = null)
    {
        self:: setUbnAndLocationHealthValues($location);

        $result = array(
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
                  )
        );

        return $result;
    }



}