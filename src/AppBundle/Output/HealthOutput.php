<?php

namespace AppBundle\Output;
use AppBundle\Entity\Location;
use Doctrine\ORM\EntityManager;

/**
 * Class HealthOutput
 */
class HealthOutput extends Output
{
    /**
     * @param Location $location
     * @return array
     */
    public static function create(EntityManager $em, Location $location = null)
    {
        self:: setUbnAndLocationHealthValues($em, $location);

        $result = array(
                  "ubn" => self::$ubn,
                    "maedi_visna_status" => self::$maediVisnaStatus,
                    "maedi_visna_check_date" => self::$maediVisnaCheckDate,
                    "maedi_visna_end_date" => self::$maediVisnaEndDate,
                    "scrapie_status" => self::$scrapieStatus,
                    "scrapie_check_date" => self::$scrapieCheckDate,
                    "scrapie_end_date" => self::$scrapieEndDate
        );

        return $result;
    }



}